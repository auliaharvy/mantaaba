<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Cache situs publik (PRD 10: 10 menit). Kunci diberi nomor versi, sehingga satu perubahan
 * halaman/menu/setting/berita membatalkan semua cache situs sekaligus tanpa perlu cache tag.
 */
class SiteCache
{
    public static function remember(string $key, \Closure $callback, int $seconds = 600): mixed
    {
        return Cache::remember('site.v' . self::version() . '.' . $key, $seconds, $callback);
    }

    public static function flush(): void
    {
        Cache::forget('site.settings');
        Cache::forever('site.version', self::version() + 1);
    }

    public static function version(): int
    {
        return (int) Cache::get('site.version', 0);
    }
}
