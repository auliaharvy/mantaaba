<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Research extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    protected $table = 'research';

    protected $fillable = ['title', 'slug', 'authors', 'abstract', 'keywords', 'category', 'cover', 'date_of_publish', 'file', 'status', 'uploaded_by'];

    protected $casts = ['date_of_publish' => 'date'];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /** Sitasi sederhana (PRD 8.8). */
    public function citation(): string
    {
        return trim(($this->authors ? "{$this->authors}. " : '') . "({$this->date_of_publish->year}). {$this->title}. " . config('app.name') . '.');
    }
}
