<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Module;
use App\Support\Blocks;
use App\Support\Html;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Materi (PRD 9.6): teks, video YouTube, file, tugas. */
class LessonController extends Controller
{
    public function create(Module $module)
    {
        $this->authorize('update', $module->skema);

        return view('dashboard.lesson.form', ['lesson' => new Lesson(['module_id' => $module->id, 'type' => 'text', 'is_published' => true]), 'module' => $module->load('skema')]);
    }

    public function store(Request $request, Module $module)
    {
        $this->authorize('update', $module->skema);
        $module->lessons()->create($this->validated($request) + ['sort_order' => (int) $module->lessons()->max('sort_order') + 1]);

        return redirect()->route('skema.show', $module->skema_id)->with('success', 'Materi ditambahkan.');
    }

    public function edit(Lesson $lesson)
    {
        $this->authorize('update', $lesson->module->skema);

        return view('dashboard.lesson.form', ['lesson' => $lesson, 'module' => $lesson->module->load('skema')]);
    }

    public function update(Request $request, Lesson $lesson)
    {
        $this->authorize('update', $lesson->module->skema);
        $lesson->update($this->validated($request, $lesson));

        return redirect()->route('skema.show', $lesson->module->skema_id)->with('success', 'Materi diubah.');
    }

    public function destroy(Lesson $lesson)
    {
        $this->authorize('delete', $lesson->module->skema);
        abort_if($lesson->progress()->whereNotNull('submitted_at')->exists(), 422, 'Materi tugas ini sudah memiliki kiriman siswa; sembunyikan saja (tidak terbit).');
        Storage::disk('local')->delete((string) $lesson->file);
        $lesson->delete();
        session()->flash('success', 'Materi dihapus.');

        return response()->noContent();
    }

    private function validated(Request $request, ?Lesson $lesson = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => ['required', Rule::in(array_keys(Lesson::TYPES))],
            'body' => 'nullable|string',
            'video_url' => ['nullable', 'required_if:type,video', 'string', 'max:255', fn ($a, $v, $fail) => Blocks::embedUrl($v) && str_contains(Blocks::embedUrl($v), 'youtube') ? null : $fail('Hanya URL video YouTube.')],
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip|max:20480',
            'due_days' => 'nullable|integer|min:1|max:365',
        ]);
        if ($data['type'] === 'file' && ! $request->hasFile('file') && ! $lesson?->file) {
            $request->validate(['file' => 'required'], ['file.required' => 'Unggah file untuk materi berjenis File.']);
        }
        $data['body'] = Html::clean($data['body'] ?? null);
        $data['is_published'] = $request->boolean('is_published');
        unset($data['file']);
        if ($request->hasFile('file')) {
            Storage::disk('local')->delete((string) $lesson?->file);
            $data['file'] = $request->file('file')->storeAs('lessons', uniqid() . '-' . preg_replace('/[^\w.-]/', '_', $request->file('file')->getClientOriginalName()), 'local');
        }

        return $data;
    }
}
