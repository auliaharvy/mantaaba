<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Thread extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    protected $fillable = ['title', 'slug', 'body', 'status', 'is_pinned', 'is_locked', 'forum_category_id', 'last_activity_at', 'user_id'];

    protected $casts = ['is_pinned' => 'boolean', 'is_locked' => 'boolean', 'last_activity_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(fn (Thread $t) => $t->last_activity_at ??= now());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(ForumCategory::class, 'forum_category_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /** Komentar tingkat atas (balasan satu level ada di Comment::replies). */
    public function rootComments()
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id')->oldest();
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
