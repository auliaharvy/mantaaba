<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;

/** Unit bisnis & laboratorium kehidupan (PRD 9.1.5). */
class BusinessUnit extends Model
{
    use FlushesSiteCache, HasSlug;

    public const TYPES = ['unit_bisnis' => 'Unit Bisnis', 'laboratorium' => 'Laboratorium Kehidupan'];

    protected string $slugSource = 'name';

    protected $fillable = ['name', 'type', 'field', 'summary', 'body', 'logo', 'cover', 'gallery_id', 'contact_name', 'phone', 'website', 'address', 'is_public', 'sort_order'];

    protected $casts = ['is_public' => 'boolean'];

    protected $attributes = ['is_public' => true, 'type' => 'unit_bisnis'];

    public function scopePublic($query)
    {
        return $query->where('is_public', true)->orderBy('sort_order')->orderBy('name');
    }

    public function gallery()
    {
        return $this->belongsTo(Gallery::class);
    }
}
