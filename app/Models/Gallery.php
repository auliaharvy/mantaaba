<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Gallery extends Model
{
    use FlushesSiteCache;

    protected $fillable = ['title', 'slug', 'description', 'cover', 'event_id', 'published_at'];

    protected $casts = ['published_at' => 'datetime'];

    protected static function booted(): void
    {
        // slug dibuat sekali agar tautan tidak putus saat judul diubah
        static::creating(fn (Gallery $g) => $g->slug ??= Str::slug($g->title) . '-' . Str::lower(Str::random(5)));
    }

    public function photos()
    {
        return $this->hasMany(GalleryPhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /** Sampul: cover atau foto pertama. */
    public function coverPath(): ?string
    {
        return $this->cover ?? $this->photos->first()?->image;
    }
}
