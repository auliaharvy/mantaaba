<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use App\Support\SiteCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** Menu header (2 level) & footer (1 level) situs (PRD 9.1.2). */
class NavMenu extends Model
{
    use FlushesSiteCache;

    public const LOCATIONS = ['header' => 'Header', 'footer' => 'Footer'];

    public const TYPES = ['page' => 'Halaman', 'route' => 'Halaman bawaan', 'url' => 'Tautan (URL)'];

    protected $fillable = ['location', 'parent_id', 'label', 'type', 'page_id', 'route_name', 'url', 'open_new_tab', 'sort_order', 'is_active'];

    protected $casts = ['open_new_tab' => 'boolean', 'is_active' => 'boolean'];

    protected $attributes = ['is_active' => true];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /** Menu aktif satu lokasi beserta anaknya, untuk header/footer situs (di-cache). */
    public static function tree(string $location): Collection
    {
        return SiteCache::remember("menu.{$location}", fn () => static::with(['page', 'children' => fn ($q) => $q->where('is_active', true)->with('page')])
            ->where('location', $location)->whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->get());
    }

    public function href(): string
    {
        return match ($this->type) {
            'page' => $this->page?->url() ?? '#',
            'route' => \Route::has((string) $this->route_name) ? route($this->route_name) : '#',
            default => (string) $this->url,
        };
    }

    public function isCurrent(): bool
    {
        $href = rtrim($this->href(), '/');

        return $href !== '#' && $href !== '' && (url()->current() === $href || ($href !== url('') && str_starts_with(url()->current(), $href . '/')));
    }
}
