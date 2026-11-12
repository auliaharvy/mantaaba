<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Skema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Impor/ekspor skema dari template JSON (database/seeders/data/skema/*.json).
 * Unit dicocokkan berdasarkan kode: unit lama diperbarui (materi tetap), unit baru ditambahkan,
 * unit yang tidak ada di JSON dihapus hanya bila belum punya materi.
 */
class SkemaImporter
{
    public const DIMENSIONS = ['knowledge', 'skill', 'character', 'social_cohesion'];

    /** @return array{skema: Skema, created: int, updated: int, removed: int, kept: array<string>} */
    public function import(array $data, ?Skema $into = null): array
    {
        $data = Validator::make($data, [
            'code' => ['required', 'string', 'max:10', Rule::when($into, fn () => Rule::in([$into->code]))],
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'month_label' => 'nullable|string|max:50',
            'objectives' => 'nullable|string',
            'competency_map' => 'nullable|array', 'final_portfolio' => 'nullable|array', 'assessment_guide' => 'nullable|array', 'notes' => 'nullable|array',
            'units' => 'required|array|min:1',
            'units.*.code' => 'required|string|max:20|distinct',
            'units.*.title' => 'required|string|max:255',
            'units.*.description' => 'nullable|string',
            'units.*.elements' => 'required|array|min:1',
            'units.*.elements.*.no' => 'required',
            'units.*.elements.*.title' => 'required|string',
            'units.*.elements.*.criteria' => 'required|array|min:1',
            'units.*.elements.*.criteria.*.no' => 'required|string',
            'units.*.elements.*.criteria.*.text' => 'required|string',
            'units.*.assessment' => 'nullable|array',
            'units.*.assessment.*.dimension' => ['required', Rule::in(self::DIMENSIONS)],
            'units.*.assessment.*.weight' => 'required|numeric|min:0|max:100',
            'units.*.assessment.*.indicator' => 'nullable|string',
            'units.*.context' => 'nullable|array',
        ], ['code.in' => 'Kode skema di JSON harus sama dengan skema yang diimpor.'], ['units.*.elements' => 'elemen', 'units.*.elements.*.criteria' => 'kriteria unjuk kerja'])->validate();

        return DB::transaction(function () use ($data, $into) {
            $skema = $into ?? Skema::firstOrNew(['code' => $data['code']]);
            $skema->fill(['name' => $data['name'], 'title' => $data['title'] ?? null, 'month_label' => $data['month_label'] ?? $skema->month_label,
                'objectives' => isset($data['objectives']) ? \App\Support\Html::clean($data['objectives']) : $skema->objectives, // ditampilkan sebagai HTML di halaman belajar
                'standard' => collect($data)->only(['competency_map', 'final_portfolio', 'assessment_guide', 'notes'])->all()]);
            $skema->sort_order ??= (int) Skema::max('sort_order') + 1;
            $skema->save();

            $stats = ['skema' => $skema, 'created' => 0, 'updated' => 0, 'removed' => 0, 'kept' => []];
            $codes = [];
            foreach (array_values($data['units']) as $i => $unit) {
                $codes[] = $unit['code'];
                $module = $skema->modules()->firstOrNew(['code' => $unit['code']]);
                $stats[$module->exists ? 'updated' : 'created']++;
                $module->fill(['title' => $unit['title'], 'summary' => $unit['description'] ?? null, 'elements' => $unit['elements'],
                    'assessment' => $unit['assessment'] ?? null, 'context' => $unit['context'] ?? null, 'sort_order' => $i])->save();
            }
            foreach ($skema->modules()->whereNotIn('code', $codes)->withCount('lessons')->get() as $old) {
                if ($old->lessons_count) {
                    $stats['kept'][] = "{$old->code} {$old->title}";
                    $old->update(['sort_order' => count($codes) + $old->sort_order]);
                } else {
                    $old->delete();
                    $stats['removed']++;
                }
            }

            return $stats;
        });
    }

    public function importFile(string $path, ?Skema $into = null): array
    {
        $data = json_decode((string) file_get_contents($path), true);
        abort_unless(is_array($data), 422, 'File bukan JSON yang valid.');

        return $this->import($data, $into);
    }

    /** Bentuk sama dengan template, bisa diedit lalu diimpor kembali. */
    public static function export(Skema $skema): array
    {
        return [
            'code' => $skema->code, 'name' => $skema->name, 'title' => $skema->title, 'month_label' => $skema->month_label, 'objectives' => $skema->objectives,
            'competency_map' => $skema->standard['competency_map'] ?? [],
            'units' => $skema->modules->map(fn (Module $m) => array_filter([
                'code' => $m->code, 'title' => $m->title, 'description' => $m->summary, 'elements' => $m->elements ?? [], 'assessment' => $m->assessment ?? [], 'context' => $m->context,
            ], fn ($v) => $v !== null))->values()->all(),
            'final_portfolio' => $skema->standard['final_portfolio'] ?? [],
            'assessment_guide' => $skema->standard['assessment_guide'] ?? [],
            'notes' => $skema->standard['notes'] ?? [],
        ];
    }
}
