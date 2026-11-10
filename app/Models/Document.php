<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Document extends Model
{
    public const CATEGORIES = ['Profil Lembaga', 'Laporan Transparansi', 'Pedoman & Tata Tertib', 'Formulir', 'Lainnya'];

    public const VISIBILITIES = ['publik' => 'Publik', 'anggota' => 'Khusus pengguna login'];

    protected $fillable = ['title', 'slug', 'category', 'file', 'visibility', 'description', 'published_at', 'sort_order'];

    protected $attributes = ['visibility' => 'publik'];

    protected $casts = ['published_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(fn (Document $d) => $d->slug ??= Str::slug($d->title) . '-' . Str::lower(Str::random(5)));
    }

    /** Tamu hanya melihat dokumen publik. */
    public function scopeVisibleTo($query, ?User $user)
    {
        return $user ? $query : $query->where('visibility', 'publik');
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
