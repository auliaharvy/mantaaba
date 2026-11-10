<?php

namespace App\Support;

use App\Models\Blog;
use App\Models\Certificate;
use App\Models\Event;

/** Statistik otomatis untuk blok `stats` (config('mantaaba.stats')). */
class SiteStats
{
    public static function value(?string $key): ?int
    {
        return match ($key) {
            'sertifikat_terbit' => Certificate::whereNull('revoked_at')->count(),
            'agenda_selesai' => Event::where('status', 'done')->count(),
            'berita' => Blog::published()->count(),
            // anggota_aktif, alumni, angkatan: tabel anggota & angkatan dibuat di M2
            default => null,
        };
    }
}
