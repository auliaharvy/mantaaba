<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Skema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Impor/ekspor skema dari template JSON (database/seeders/data/skema/*.json).
 * Struktur: skema → unit kompetensi → modul (satu per elemen, berisi KUK) → materi.
 * Dicocokkan berdasarkan kode (unit MTB-F-0001, modul MTB-F-0001.1): yang lama diperbarui (materi tetap), yang baru ditambahkan,
 * modul yang hilang dari JSON dihapus hanya bila belum punya materi.
 */
class SkemaImporter
{
    public const DIMENSIONS = ['knowledge', 'skill', 'character', 'social_cohesion'];

    /** @return array{skema: Skema, created: int, updated: int, removed: int, kept: array<string>} jumlah unit; kept = modul bermateri yang dipertahankan */
    public function import(array $data, ?Skema $into = null): array
    {
        $data = Validator::make($data, [
            'code' => ['required', 'string', 'max:10', Rule::when($into, fn () => Rule::in([$into->code]))],
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'month_label' => 'nullable|string|max:50',
            'objectives' => 'nullable|string',
            'competency_map' => 'nullable|array', 'final_portfolio' => 'nullable|array', 'assessment_guide' => 'nullable|array', 'notes' => 'nullable|array',
            'units' => 'required|array|min:1',
            'units.*.code' => 'required|string|max:20|distinct',
            'units.*.title' => 'required|string|max:255',
            'units.*.key_function' => 'nullable|string|max:500',
            'units.*.main_function' => 'nullable|string|max:255',
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
            'units.*.character' => 'nullable|array',
            'units.*.character.technical' => 'nullable|string|max:500', 'units.*.character.asabiyah' => 'nullable|string|max:1000', 'units.*.character.fastabiqul' => 'nullable|string|max:1000',
        ], ['code.in' => 'Kode skema di JSON harus sama dengan skema yang diimpor.'], ['units.*.elements' => 'elemen', 'units.*.elements.*.criteria' => 'kriteria unjuk kerja'])->validate();

        return DB::transaction(function () use ($data, $into) {
            $skema = $into ?? Skema::firstOrNew(['code' => $data['code']]);
            $skema->fill(['name' => $data['name'], 'title' => $data['title'] ?? null, 'month_label' => $data['month_label'] ?? $skema->month_label,
                'description' => $data['description'] ?? $skema->description,
                'objectives' => isset($data['objectives']) ? \App\Support\Html::clean($data['objectives']) : $skema->objectives, // ditampilkan sebagai HTML di halaman belajar
                'standard' => collect($data)->only(['competency_map', 'final_portfolio', 'assessment_guide', 'notes'])->all()]);
            $skema->sort_order ??= (int) Skema::max('sort_order') + 1;
            $skema->save();

            $stats = ['skema' => $skema, 'created' => 0, 'updated' => 0, 'removed' => 0, 'kept' => []];
            $unitCodes = [];
            $moduleCodes = [];
            $order = 0;
            // Fungsi Kunci/Utama per unit; template lama cukup mengisi competency_map
            $map = collect($data['competency_map'] ?? [])->flatMap(fn ($row) => collect($row['units'] ?? [])->mapWithKeys(fn ($code) => [$code => $row]));
            foreach (array_values($data['units']) as $i => $item) {
                $unitCodes[] = $item['code'];
                $unit = $skema->units()->firstOrNew(['code' => $item['code']]);
                $stats[$unit->exists ? 'updated' : 'created']++;
                $unit->fill(['title' => $item['title'], 'description' => $item['description'] ?? null,
                    'key_function' => $item['key_function'] ?? $map[$item['code']]['key_function'] ?? null,
                    'main_function' => $item['main_function'] ?? $map[$item['code']]['main_function'] ?? null, 'assessment' => $item['assessment'] ?? null,
                    'context' => $item['context'] ?? null, 'character' => $item['character'] ?? null, 'sort_order' => $i])->save();
                // setiap elemen unit = satu modul pembelajaran (materi menempel di modul)
                foreach ($item['elements'] as $element) {
                    $code = $item['code'] . '.' . $element['no'];
                    $moduleCodes[] = $code;
                    $skema->modules()->updateOrCreate(['code' => $code], ['competency_unit_id' => $unit->id, 'title' => $element['title'],
                        'criteria' => $element['criteria'], 'sort_order' => $order++]);
                }
            }
            // modul (elemen) yang tidak ada lagi: hapus bila belum punya materi, selain itu dipertahankan
            foreach ($skema->modules()->where(fn ($q) => $q->whereNotIn('code', $moduleCodes)->orWhereNull('code'))->withCount('lessons')->get() as $old) {
                if ($old->lessons_count) {
                    $stats['kept'][] = trim("{$old->code} {$old->title}");
                    $old->update(['sort_order' => $order++]);
                } else {
                    $old->delete();
                }
            }
            $stats['removed'] = $skema->units()->whereNotIn('code', $unitCodes)->delete();

            return $stats;
        });
    }

    public function importFile(string $path, ?Skema $into = null): array
    {
        $data = json_decode((string) file_get_contents($path), true);
        abort_unless(is_array($data), 422, 'File bukan JSON yang valid.');

        return $this->import($data, $into);
    }

    /** Bentuk sama dengan template, bisa diedit lalu diimpor kembali (nomor elemen dari akhiran kode modul). */
    public static function export(Skema $skema): array
    {
        $skema->loadMissing('units.modules');

        return [
            'code' => $skema->code, 'name' => $skema->name, 'title' => $skema->title, 'description' => $skema->description, 'month_label' => $skema->month_label, 'objectives' => $skema->objectives,
            'competency_map' => $skema->standard['competency_map'] ?? [],
            'units' => $skema->units->map(fn ($unit) => array_filter([
                'code' => $unit->code, 'title' => $unit->title, 'key_function' => $unit->key_function, 'main_function' => $unit->main_function, 'description' => $unit->description,
                'elements' => $unit->modules->values()->map(fn (Module $m, $i) => ['no' => \Illuminate\Support\Str::contains((string) $m->code, '.') ? \Illuminate\Support\Str::afterLast($m->code, '.') : (string) ($i + 1),
                    'title' => $m->title, 'criteria' => $m->criteria ?? []])->all(),
                'assessment' => $unit->assessment ?? [], 'context' => $unit->context, 'character' => $unit->character,
            ], fn ($v) => $v !== null))->values()->all(),
            'final_portfolio' => $skema->standard['final_portfolio'] ?? [],
            'assessment_guide' => $skema->standard['assessment_guide'] ?? [],
            'notes' => $skema->standard['notes'] ?? [],
        ];
    }
}
