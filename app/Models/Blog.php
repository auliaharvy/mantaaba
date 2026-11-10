<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blog extends Model
{
    use FlushesSiteCache, HasFactory, HasSlug, SoftDeletes;

    /** PRD 9.1.4: tanpa review; `published` dengan published_at di masa depan = terjadwal. */
    public const STATUSES = ['draft' => 'Draf', 'published' => 'Terbit'];

    protected $fillable = ['title', 'slug', 'excerpt', 'body', 'thumbnail', 'status', 'published_at', 'category_id', 'user_id'];

    protected $casts = ['published_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('published_at', '<=', now());
    }

    public function excerptText(int $limit = 160): string
    {
        return $this->excerpt ?: \Illuminate\Support\Str::limit(trim(strip_tags($this->body)), $limit);
    }
}
