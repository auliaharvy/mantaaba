<?php

namespace App\Models\Concerns;

use App\Support\SiteCache;

/** Cache situs publik dibersihkan saat konten berubah (AC M1). */
trait FlushesSiteCache
{
    public static function bootFlushesSiteCache(): void
    {
        static::saved(fn () => SiteCache::flush());
        static::deleted(fn () => SiteCache::flush());
    }
}
