<?php

namespace App\Support;

/** Daftar permission & paket hak akses dari config/mantaaba.php (PRD 5.3–5.4). */
class Permissions
{
    /** @return array<string, string> permission => label, dikelompokkan per modul */
    public static function forModule(string $module): array
    {
        $def = config("mantaaba.modules.{$module}");
        $labels = ['view' => 'Lihat', 'create' => 'Tambah', 'update' => 'Ubah', 'delete' => 'Hapus'];
        $out = [];
        foreach ($def['actions'] ?? [] as $action) {
            $out["{$module}-{$action}"] = $labels[$action];
        }

        return $out + ($def['extra'] ?? []);
    }

    /** @return string[] semua nama permission */
    public static function all(): array
    {
        return collect(config('mantaaba.modules'))->keys()
            ->flatMap(fn ($module) => array_keys(self::forModule($module)))
            ->values()->all();
    }

    /** @return string[] permission dalam satu paket */
    public static function package(string $key): array
    {
        $def = config("mantaaba.packages.{$key}");
        abort_unless($def, 404);

        return collect($def['modules'] ?? [])
            ->flatMap(fn ($module) => array_keys(self::forModule($module)))
            ->merge($def['permissions'] ?? [])
            ->diff($def['except'] ?? [])
            ->unique()->values()->all();
    }
}
