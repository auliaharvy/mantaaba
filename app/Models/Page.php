<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use Illuminate\Database\Eloquent\Model;

/** Halaman situs berbasis blok atau rich text (PRD 9.1.3). */
class Page extends Model
{
    use FlushesSiteCache;

    public const TEMPLATES = ['blocks' => 'Susunan blok', 'rich' => 'Teks bebas (editor)'];

    protected $fillable = ['slug', 'title', 'excerpt', 'template', 'blocks', 'body', 'cover', 'meta_title', 'meta_description', 'is_published', 'show_in_sitemap', 'updated_by'];

    protected $casts = ['blocks' => 'array', 'is_published' => 'boolean', 'is_system' => 'boolean', 'show_in_sitemap' => 'boolean'];

    protected $attributes = ['template' => 'blocks', 'is_published' => true, 'show_in_sitemap' => true];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Halaman terbit berdasarkan slug, atau 404. */
    public static function published(string $slug): self
    {
        return static::where('slug', $slug)->where('is_published', true)->firstOrFail();
    }

    public function url(): string
    {
        return $this->slug === 'home' ? route('site.home') : route('site.page', $this->slug);
    }

    /** Blok yang tampil di situs (tidak disembunyikan, jenisnya dikenal), field kosong dilengkapi null. */
    public function visibleBlocks(): array
    {
        $definitions = config('mantaaba.blocks');

        return collect($this->blocks ?? [])
            ->filter(fn ($b) => empty($b['hidden']) && isset($definitions[$b['type'] ?? '']))
            ->map(fn ($b) => ['type' => $b['type'], 'data' => ($b['data'] ?? []) + array_fill_keys(array_keys($definitions[$b['type']]['fields']), null)])
            ->values()->all();
    }

    public function menus()
    {
        return $this->hasMany(NavMenu::class);
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
