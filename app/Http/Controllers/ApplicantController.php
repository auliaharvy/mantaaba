<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Batch;
use App\Models\Setting;
use App\Services\AdmissionDecider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

/** Seleksi pendaftar di dashboard (PRD 9.3). */
class ApplicantController extends Controller
{
    public function __construct(private AdmissionDecider $decider)
    {
        $this->middleware('permission:admission-view')->only(['index', 'datatable', 'show', 'document']);
    }

    public function index(Request $request)
    {
        $batches = Batch::orderByDesc('code')->get();
        $batch = $batches->firstWhere('id', (int) $request->query('batch')) ?? $batches->firstWhere('status', 'open') ?? $batches->first();
        $counts = $batch ? $batch->applicants()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status') : collect();
        $districts = $batch ? $batch->applicants()->distinct()->orderBy('district')->pluck('district') : collect();

        return view('dashboard.applicant.index', compact('batches', 'batch', 'counts', 'districts') + ['status' => $request->query('status', 'baru')]);
    }

    public function datatable(Request $request)
    {
        $query = Applicant::query()->where('batch_id', $request->integer('batch'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->input('priority') === '1', fn ($q) => $q->where('is_priority', true))
            ->when($request->filled('district'), fn ($q) => $q->where('district', $request->input('district')))
            ->select(['id', 'registration_number', 'name', 'nik', 'district', 'family_status', 'economic_status', 'is_priority', 'scores', 'status', 'created_at']);

        return DataTables::eloquent($query)
            ->editColumn('name', fn ($a) => e($a->name) . ($a->is_priority ? ' <span class="text-warning" title="Prioritas">★</span>' : ''))
            ->editColumn('family_status', fn ($a) => Applicant::FAMILY_STATUSES[$a->family_status] . ($a->economic_status === 'kurang_mampu' ? ' · kurang mampu' : ''))
            ->addColumn('score', fn ($a) => $a->averageScore() ? number_format($a->averageScore(), 1, ',', '') : '-')
            ->editColumn('status', fn ($a) => Applicant::STATUSES[$a->status])
            ->editColumn('created_at', fn ($a) => $a->created_at->translatedFormat('d M Y'))
            ->addColumn('action', fn ($a) => view('components.dashboard.row-actions', ['show' => route('applicant.show', $a), 'edit' => null, 'delete' => null, 'confirm' => null])->render())
            ->removeColumn('nik', 'scores')
            ->filterColumn('name', fn ($q, $keyword) => $q->where(fn ($w) => $w->where('name', 'like', "%{$keyword}%")->orWhere('nik', 'like', "%{$keyword}%")))
            ->rawColumns(['name', 'action'])->addIndexColumn()->make(true);
    }

    public function show(Applicant $applicant)
    {
        return view('dashboard.applicant.show', ['applicant' => $applicant->load('batch', 'member', 'decider')]);
    }

    /** Berkas pendaftar: hanya untuk pemegang admission-view (AC 9.3). */
    public function document(Applicant $applicant, string $key)
    {
        $path = $applicant->documents[$key] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, "{$applicant->registration_number}-{$key}." . pathinfo($path, PATHINFO_EXTENSION));
    }

    public function scores(Request $request, Applicant $applicant)
    {
        $this->authorize('update', $applicant);
        $rules = collect(Applicant::SCORES)->mapWithKeys(fn ($label, $key) => ["scores.{$key}" => 'nullable|integer|min:1|max:5'])->all();
        $data = $request->validate($rules + ['interview_note' => 'nullable|string|max:3000', 'home_visit_note' => 'nullable|string|max:3000']);
        $applicant->update(['scores' => array_map(fn ($v) => $v === null ? null : (int) $v, $data['scores'] ?? [])] + collect($data)->only(['interview_note', 'home_visit_note'])->all());

        return back()->with('success', 'Penilaian disimpan.');
    }

    public function move(Request $request, Applicant $applicant)
    {
        $this->authorize('update', $applicant);
        $data = $request->validate([
            'status' => ['required', Rule::in($applicant->nextStages())],
            'note' => 'required|string|max:1000',
            'scheduled_at' => 'nullable|date',
        ], ['note.required' => 'Catatan wajib diisi saat pindah tahap.']);
        $this->decider->move($applicant, $data['status'], $data['note'], $data['scheduled_at'] ?? null);
        $extra = $applicant->interview_at && $data['status'] === 'wawancara' ? 'Jadwal wawancara: ' . $applicant->interview_at->translatedFormat('l, j F Y H:i')
            : ($applicant->home_visit_at && $data['status'] === 'kunjungan_rumah' ? 'Jadwal kunjungan rumah: ' . $applicant->home_visit_at->translatedFormat('l, j F Y H:i') : null);

        return back()->with('success', 'Tahap diperbarui.')->with('wa_link', Setting::waLink($applicant->phone, AdmissionDecider::whatsappText($applicant, $extra)));
    }

    public function accept(Request $request, Applicant $applicant)
    {
        $this->authorize('decide', $applicant);
        $note = $request->validate(['note' => 'nullable|string|max:1000'])['note'] ?? null;
        [$member, $url] = $this->decider->accept($applicant, $note);
        $applicant->refresh();
        $text = AdmissionDecider::whatsappText($applicant, "Akun Anda: {$member->user->email}\nAtur password: {$url}");

        return redirect()->route('applicant.show', $applicant)->with('success', "Diterima sebagai anggota {$member->member_number}.")
            ->with('wa_link', Setting::waLink($applicant->phone, $text))->with('password_link', $url);
    }

    public function reject(Request $request, Applicant $applicant)
    {
        $this->authorize('decide', $applicant);
        $data = $request->validate(['status' => ['required', Rule::in(['ditolak', 'mengundurkan_diri'])], 'note' => 'required|string|max:1000']);
        $this->decider->reject($applicant, $data['status'], $data['note']);

        return back()->with('success', 'Keputusan disimpan.')->with('wa_link', Setting::waLink($applicant->phone, AdmissionDecider::whatsappText($applicant->fresh())));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Applicant::class);
        $batch = Batch::findOrFail($request->integer('batch'));
        $columns = ['registration_number', 'name', 'nik', 'gender', 'birth_place', 'birth_date', 'phone', 'email', 'address', 'village', 'district', 'regency',
            'education', 'school_name', 'family_status', 'economic_status', 'guardian_phone', 'info_source', 'is_priority', 'status', 'created_at'];

        return response()->streamDownload(function () use ($batch, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [...$columns, 'skor_rata2']);
            $batch->applicants()->orderBy('registration_number')->each(function (Applicant $a) use ($out, $columns) {
                fputcsv($out, [...array_map(fn ($c) => $a->{$c} instanceof \DateTimeInterface ? $a->{$c}->format('Y-m-d') : $a->{$c}, $columns), $a->averageScore()]);
            });
            fclose($out);
        }, "pendaftar-{$batch->code}.csv", ['Content-Type' => 'text/csv']);
    }
}
