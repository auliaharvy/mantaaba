<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Skema;
use Illuminate\Http\Request;

/** Modul dalam skema (PRD 9.6). */
class ModuleController extends Controller
{
    public function store(Request $request, Skema $skema)
    {
        $this->authorize('update', $skema);
        $skema->modules()->create($this->validated($request) + ['sort_order' => (int) $skema->modules()->max('sort_order') + 1]);

        return redirect()->route('skema.show', $skema)->with('success', 'Modul ditambahkan.');
    }

    public function update(Request $request, Module $module)
    {
        $this->authorize('update', $module->skema);
        $module->update($this->validated($request));

        return redirect()->route('skema.show', $module->skema_id)->with('success', 'Modul diubah.');
    }

    public function destroy(Module $module)
    {
        $this->authorize('update', $module->skema);
        abort_if($module->lessons()->exists(), 422, 'Hapus atau pindahkan materi di modul ini dulu.');
        $module->delete();
        session()->flash('success', 'Modul dihapus.');

        return response()->noContent();
    }

    /** Urutan modul & materi dari drag (id dalam urutan tampil). */
    public function reorder(Request $request, Skema $skema)
    {
        $this->authorize('update', $skema);
        $data = $request->validate(['modules' => 'array', 'modules.*' => 'integer', 'lessons' => 'array', 'lessons.*' => 'integer']);
        foreach (array_values($data['modules'] ?? []) as $i => $id) {
            $skema->modules()->whereKey($id)->update(['sort_order' => $i]);
        }
        $moduleIds = $skema->modules()->pluck('id');
        foreach (array_values($data['lessons'] ?? []) as $i => $id) {
            \App\Models\Lesson::whereKey($id)->whereIn('module_id', $moduleIds)->update(['sort_order' => $i]);
        }

        return redirect()->route('skema.show', $skema)->with('success', 'Urutan disimpan.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['code' => 'nullable|string|max:20', 'title' => 'required|string|max:255', 'summary' => 'nullable|string|max:2000']);
    }
}
