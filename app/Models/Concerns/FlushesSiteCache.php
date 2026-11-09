<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Cache;

/** Cache situs publik (PRD 9: beranda & statistik 10 menit) dibersihkan saat konten berubah. */
trait FlushesSiteCache
{
    public const SITE_CACHE_KEYS = ['site.home', 'site.stats', 'site.settings'];

    public static function bootFlushesSiteCache(): void
    {
        $flush = fn () => array_map(fn ($key) => Cache::forget($key), self::SITE_CACHE_KEYS);
        static::saved($flush);
        static::deleted($flush);
    }
}
