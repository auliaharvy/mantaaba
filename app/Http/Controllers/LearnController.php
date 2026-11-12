<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Skema;
use App\Models\SkemaEnrollment;
use App\Models\User;
use App\Notifications\AssignmentSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/** Sisi anggota: belajar per skema (PRD 9.6). Skema terkunci → 403. */
class LearnController extends Controller
{
    public function index(Request $request)
    {
        $member = $request->user()->member;
        abort_unless($member, 404);
        $enrollments = $member->enrollments()->with('skema', 'result', 'result.activeCertificate')->get()->sortBy(fn ($e) => [$e->skema->sort_order, $e->skema->id]);

        return view('dashboard.learn.index', compact('member', 'enrollments'));
    }

    public function skema(Request $request, Skema $skema)
    {
        $enrollment = $this->enrollment($request, $skema);
        $skema->load(['units', 'modules.lessons' => fn ($q) => $q->where('is_published', true)]);
        $progress = LessonProgress::where('user_id', $request->user()->id)->whereIn('lesson_id', $skema->modules->flatMap->lessons->pluck('id'))->get()->keyBy('lesson_id');

        return view('dashboard.learn.skema', compact('skema', 'enrollment', 'progress'));
    }

    public function lesson(Request $request, Lesson $lesson)
    {
        abort_unless($lesson->is_published, 404);
        $skema = $lesson->module->skema;
        $enrollment = $this->enrollment($request, $skema);
        $lessons = $skema->modules()->with(['lessons' => fn ($q) => $q->where('is_published', true)])->get()->flatMap->lessons->values();
        $index = $lessons->search(fn ($l) => $l->id === $lesson->id);
        $progress = LessonProgress::firstWhere(['user_id' => $request->user()->id, 'lesson_id' => $lesson->id]);
        $due = $lesson->due_days && $enrollment->opened_at ? $enrollment->opened_at->copy()->addDays($lesson->due_days) : null;

        return view('dashboard.learn.lesson', [
            'lesson' => $lesson, 'skema' => $skema, 'progress' => $progress, 'due' => $due,
            'position' => $index + 1, 'total' => $lessons->count(), 'previous' => $lessons[$index - 1] ?? null, 'next' => $lessons[$index + 1] ?? null,
        ]);
    }

    public function complete(Request $request, Lesson $lesson)
    {
        $this->enrollment($request, $lesson->module->skema);
        abort_if($lesson->isAssignment(), 422, 'Materi tugas selesai saat tugas dikumpulkan.');
        LessonProgress::updateOrCreate(['user_id' => $request->user()->id, 'lesson_id' => $lesson->id], ['completed_at' => now()]);

        return back()->with('success', 'Materi ditandai selesai.');
    }

    public function submit(Request $request, Lesson $lesson)
    {
        $this->enrollment($request, $lesson->module->skema);
        abort_unless($lesson->isAssignment(), 404);
        $progress = LessonProgress::firstOrNew(['user_id' => $request->user()->id, 'lesson_id' => $lesson->id]);
        // PRD 9.6: tugas yang sudah dinilai tidak bisa diganti kecuali dibuka ulang mentor
        abort_if($progress->reviewed_at, 422, 'Tugas sudah dinilai. Minta mentor membuka ulang bila perlu memperbaiki.');
        $data = $request->validate([
            'submission_file' => 'nullable|required_without:submission_text|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip|max:10240',
            'submission_text' => 'nullable|required_without:submission_file|string|max:10000',
        ], ['required_without' => 'Unggah file atau tulis jawaban.']);
        if ($request->hasFile('submission_file')) {
            Storage::disk('local')->delete((string) $progress->submission_file);
            $progress->submission_file = $request->file('submission_file')->store("submissions/{$lesson->id}", 'local');
        }
        $progress->fill(['submission_text' => $data['submission_text'] ?? $progress->submission_text, 'submitted_at' => now(), 'completed_at' => now()])->save();
        Notification::send(User::withPermission('learning-review'), new AssignmentSubmitted($progress->load('user', 'lesson')));

        return back()->with('success', 'Tugas terkirim. Mentor akan memberi umpan balik.');
    }

    /** File materi: siswa yang skemanya terbuka, atau pengurus learning-view. */
    public function lessonFile(Request $request, Lesson $lesson)
    {
        if (! $request->user()->can('learning-view')) {
            abort_unless($lesson->is_published, 404);
            $this->enrollment($request, $lesson->module->skema);
        }
        abort_unless($lesson->file && Storage::disk('local')->exists($lesson->file), 404);

        return Storage::disk('local')->download($lesson->file, preg_replace('/^[^-]+-/', '', basename($lesson->file)));
    }

    /** File tugas: hanya pemilik & pengurus learning-view (AC 9.6). */
    public function submissionFile(Request $request, LessonProgress $progress)
    {
        abort_unless($progress->user_id === $request->user()->id || $request->user()->can('learning-view'), 403);
        abort_unless($progress->submission_file && Storage::disk('local')->exists($progress->submission_file), 404);

        return Storage::disk('local')->download($progress->submission_file, "tugas-{$progress->lesson_id}-{$progress->user_id}." . pathinfo($progress->submission_file, PATHINFO_EXTENSION));
    }

    private function enrollment(Request $request, Skema $skema): SkemaEnrollment
    {
        $enrollment = $request->user()->member?->enrollments()->where('skema_id', $skema->id)->first();
        abort_if(! $enrollment || $enrollment->isLocked(), 403, 'Skema ini belum dibuka.');

        return $enrollment;
    }
}
