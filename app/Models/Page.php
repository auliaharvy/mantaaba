<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use FlushesSiteCache;

    protected $fillable = ['title', 'excerpt', 'body', 'cover', 'meta_title', 'meta_description', 'is_published', 'updated_by'];

    protected $casts = ['is_published' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Halaman terbit berdasarkan slug, atau 404. */
    public static function published(string $slug): self
    {
        return static::where('slug', $slug)->where('is_published', true)->firstOrFail();
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
