<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Document extends Model
{
    public const CATEGORIES = ['Profil Lembaga', 'Laporan Transparansi', 'Pedoman & Tata Tertib', 'Formulir', 'Lainnya'];

    protected $fillable = ['title', 'slug', 'category', 'file', 'description', 'published_at', 'sort_order'];

    protected $casts = ['published_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(fn (Document $d) => $d->slug ??= Str::slug($d->title) . '-' . Str::lower(Str::random(5)));
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
