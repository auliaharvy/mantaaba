<?php

namespace App\Http\Controllers;

use App\Jobs\IssueSkemaCertificates;
use App\Models\Batch;
use App\Models\CoachingNote;
use App\Models\LessonProgress;
use App\Models\Setting;
use App\Models\Skema;
use App\Models\SkemaEnrollment;
use App\Models\SkemaResult;
use App\Services\SkemaScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Penilaian 4 dimensi massal per angkatan & skema (PRD 9.7). */
class AssessmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:assessment-view')->only('index');
        $this->middleware('permission:assessment-update')->only('store');
    }

    public function index(Request $request)
    {
        $batches = Batch::whereIn('status', ['running', 'done', 'selection'])->orderByDesc('code')->get();
        $skemas = Skema::ordered()->get();
        $batch = $batches->firstWhere('id', $request->integer('batch')) ?? $batches->first();
        $skema = $skemas->firstWhere('id', $request->integer('skema')) ?? $skemas->first();
        $rows = $batch && $skema ? $this->rows($batch, $skema) : collect();
        $setting = Setting::current();

        return view('dashboard.assessment.index', compact('batches', 'skemas', 'batch', 'skema', 'rows', 'setting') + ['weights' => SkemaScore::weights()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'results' => 'required|array',
            'results.*.knowledge' => 'nullable|numeric|min:0|max:100',
            'results.*.skill' => 'nullable|numeric|min:0|max:100',
            'results.*.character' => 'nullable|numeric|min:0|max:100',
            'results.*.social_cohesion' => 'nullable|numeric|min:0|max:100',
            'results.*.notes' => 'nullable|string|max:1000',
        ]);
        $setting = Setting::current();
        $saved = 0;
        DB::transaction(function () use ($data, $request, $setting, &$saved) {
            foreach ($data['results'] as $enrollmentId => $row) {
                $scores = array_intersect_key($row, SkemaResult::DIMENSIONS);
                if (count(array_filter($scores, fn ($v) => $v !== null && $v !== '')) < 4) {
                    continue; // baris belum lengkap dilewati
                }
                $enrollment = SkemaEnrollment::with('skema', 'result')->findOrFail($enrollmentId);
                abort_if($enrollment->isLocked(), 422, 'Skema belum dibuka untuk siswa ini.');
                $result = $enrollment->result;
                if ($result?->locked_at) {
                    $changed = collect($scores)->contains(fn ($v, $k) => round((float) $v, 2) !== round((float) $result->{$k}, 2));
                    abort_if($changed, 422, 'Cabut sertifikat terlebih dahulu untuk mengubah nilai yang terkunci.');
                    continue;
                }
                $calc = SkemaScore::calculate($scores, SkemaScore::weights(), $enrollment->skema->passScore(), (int) $setting->min_character_score);
                SkemaResult::updateOrCreate(['enrollment_id' => $enrollment->id], $scores + $calc + [
                    'notes' => $row['notes'] ?? null, 'assessed_by' => $request->user()->id, 'assessed_at' => now(),
                ]);
                $enrollment->update(['status' => $calc['passed'] ? 'lulus' : 'belum_lulus']);
                $saved++;
            }
        });

        return back()->with('success', "{$saved} nilai disimpan.");
    }

    public function issue(Request $request)
    {
        abort_unless($request->user()->can('certificate-issue'), 403);
        $data = $request->validate(['batch' => 'required|exists:batches,id', 'skema' => 'required|exists:skemas,id']);
        IssueSkemaCertificates::dispatch(Batch::find($data['batch']), Skema::find($data['skema']));

        return back()->with('success', 'Sertifikat siswa yang lulus sedang diterbitkan di latar belakang. Siswa menerima notifikasi saat siap.');
    }

    /** Siswa angkatan pada skema + nilai pendukung (tugas, pembinaan). Kehadiran kelas & piket menyusul M4. */
    private function rows(Batch $batch, Skema $skema)
    {
        $enrollments = SkemaEnrollment::with('member.user', 'result.activeCertificate')->where('skema_id', $skema->id)
            ->whereIn('member_id', $batch->members()->select('id'))->get()->sortBy('member.name')->values();
        $lessonIds = $skema->lessons()->pluck('lessons.id');

        return $enrollments->map(function (SkemaEnrollment $e) use ($lessonIds) {
            $since = $e->opened_at ?? now()->subMonths(2);
            $notes = CoachingNote::where('member_id', $e->member_id)->where('date', '>=', $since->toDateString())->get(['type', 'dimension']);

            return [
                'enrollment' => $e,
                'assignment' => $e->member->user_id ? LessonProgress::where('user_id', $e->member->user_id)->whereIn('lesson_id', $lessonIds)->whereNotNull('reviewed_at')->avg('score') : null,
                'positive' => $notes->where('type', 'positif')->count(),
                'violations' => $notes->filter(fn ($n) => str_starts_with($n->type, 'pelanggaran'))->count(),
                'dimensions' => $notes->groupBy('dimension')->map->count(),
            ];
        });
    }
}
