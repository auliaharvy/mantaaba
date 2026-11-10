<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/** Blok halaman (PRD 9.1.3): definisi di config('mantaaba.blocks'), validasi & normalisasi input editor. */
class Blocks
{
    public const IMAGE_DIR = 'pages/blocks';

    public static function definitions(): array
    {
        return config('mantaaba.blocks');
    }

    /** Blok kosong dengan nilai default; blok berulang dimulai dengan satu item kosong. */
    public static function blank(string $type): array
    {
        $def = self::definitions()[$type];
        $data = array_map(fn ($f) => $f['default'] ?? null, $def['fields']);
        if (isset($def['items'])) {
            $data['items'] = [array_map(fn () => null, $def['items']['fields'])];
        }

        return ['type' => $type, 'hidden' => false, 'data' => $data];
    }

    /** Pilihan field select; `stats` merujuk ke daftar statistik otomatis. */
    public static function options(array $field): array
    {
        return $field['options'] === 'stats' ? config('mantaaba.stats') : $field['options'];
    }

    /** URL embed yang aman ditampilkan, atau null bila domain di luar whitelist (YouTube, Google Maps). */
    public static function embedUrl(?string $url): ?string
    {
        if (preg_match('#^https://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})#', (string) $url, $m)) {
            return "https://www.youtube-nocookie.com/embed/{$m[1]}";
        }

        return preg_match('#^https://(?:www\.)?google\.com/maps/embed\?[^"\'<>\s]+$#', (string) $url) ? $url : null;
    }

    /** Validasi blok dari form editor, simpan unggahan gambar, kembalikan daftar blok bersih (urutan = urutan form). */
    public static function fromRequest(Request $request): array
    {
        $input = $request->input('blocks', []);
        $definitions = self::definitions();
        $rules = ['blocks' => 'nullable|array|max:40'];
        foreach ($input as $key => $block) {
            $rules["blocks.{$key}.type"] = ['required', Rule::in(array_keys($definitions))];
            $def = $definitions[$block['type'] ?? ''] ?? null;
            if (! $def) {
                continue;
            }
            foreach ($def['fields'] as $name => $field) {
                $rules += self::rules("blocks.{$key}.data.{$name}", $field);
            }
            if (isset($def['items'])) {
                $rules["blocks.{$key}.data.items"] = 'nullable|array|max:' . $def['items']['max'];
                foreach ($def['items']['fields'] as $name => $field) {
                    $rules += self::rules("blocks.{$key}.data.items.*.{$name}", $field);
                }
            }
        }
        $request->validate($rules, [], self::attributeNames($input));

        $blocks = [];
        foreach ($input as $key => $block) {
            $def = $definitions[$block['type']];
            $data = self::clean($def['fields'], $block['data'] ?? [], $request, "blocks.{$key}.data");
            if (isset($def['items'])) {
                $data['items'] = [];
                foreach (array_values($block['data']['items'] ?? []) as $j => $item) {
                    $originalKey = array_keys($block['data']['items'])[$j];
                    $data['items'][] = self::clean($def['items']['fields'], $item, $request, "blocks.{$key}.data.items.{$originalKey}");
                }
            }
            $blocks[] = ['type' => $block['type'], 'hidden' => ! empty($block['hidden']), 'data' => $data];
        }

        return $blocks;
    }

    private static function rules(string $path, array $field): array
    {
        $base = [empty($field['required']) ? 'nullable' : 'required'];
        $rules = match ($field['type']) {
            'text' => ['string', 'max:255'],
            'textarea' => ['string', 'max:2000'],
            'rich' => ['string', 'max:100000'],
            'url' => ['string', 'max:500', 'regex:#^(https?://|/|\#|mailto:|tel:)#i'],
            'image' => ['string', 'max:255', 'regex:#^' . self::IMAGE_DIR . '/[\w.-]+$#'],
            'number' => ['integer', 'min:' . ($field['min'] ?? 0), 'max:' . ($field['max'] ?? 1000000)],
            'select' => [Rule::in(array_keys(self::options($field)))],
            'icon' => [Rule::in(config('mantaaba.icons'))],
            'embed' => ['string', 'max:1000', fn ($attr, $value, $fail) => self::embedUrl($value) ? null : $fail('Hanya URL YouTube atau Google Maps embed (https://www.google.com/maps/embed?...) yang diizinkan.')],
            'gallery' => ['integer', 'exists:galleries,id'],
            'units' => ['array'],
        };
        $out = [$path => array_merge($base, $rules)];
        if ($field['type'] === 'image') {
            $out["{$path}_upload"] = 'nullable|' . Upload::IMAGE_RULE;
        }
        if ($field['type'] === 'units') {
            $out["{$path}.*"] = 'integer|exists:business_units,id';
        }

        return $out;
    }

    private static function clean(array $fields, array $data, Request $request, string $path): array
    {
        $out = [];
        foreach ($fields as $name => $field) {
            $value = $data[$name] ?? null;
            $value = match ($field['type']) {
                'rich' => Html::clean($value),
                'number', 'gallery' => $value === null ? null : (int) $value,
                'units' => array_map('intval', Arr::wrap($value)),
                // shortcut: gambar lama tidak dihapus (bisa dipakai blok hasil duplikat); bersihkan berkala bila storage menumpuk
                'image' => $request->hasFile("{$path}.{$name}_upload")
                    ? Upload::store($request->file("{$path}.{$name}_upload"), self::IMAGE_DIR)
                    : (empty($data["{$name}_remove"]) ? $value : null),
                default => $value,
            };
            $out[$name] = $value;
        }

        return $out;
    }

    /** Nama field ramah untuk pesan error, mis. "Hero #1 › Judul". */
    private static function attributeNames(array $input): array
    {
        $names = [];
        $n = 0;
        foreach ($input as $key => $block) {
            $n++;
            $def = self::definitions()[$block['type'] ?? ''] ?? null;
            if (! $def) {
                continue;
            }
            $prefix = \Illuminate\Support\Str::before($def['label'], ' (') . " #{$n}";
            foreach ($def['fields'] as $name => $field) {
                $names["blocks.{$key}.data.{$name}"] = "{$prefix} › {$field['label']}";
            }
            foreach ($def['items']['fields'] ?? [] as $name => $field) {
                $names["blocks.{$key}.data.items.*.{$name}"] = "{$prefix} › {$def['items']['label']} › {$field['label']}";
            }
        }

        return $names;
    }
}
