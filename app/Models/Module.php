<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Unit kompetensi (modul) dalam skema (PRD 9.6): elemen + KUK, aturan penilaian 4 dimensi, konteks praktik. */
class Module extends Model
{
    protected $fillable = ['skema_id', 'code', 'title', 'summary', 'elements', 'assessment', 'context', 'sort_order'];

    protected $casts = ['elements' => 'array', 'assessment' => 'array', 'context' => 'array'];

    public function criteriaCount(): int
    {
        return collect($this->elements)->sum(fn ($e) => count($e['criteria'] ?? []));
    }

    public function skema()
    {
        return $this->belongsTo(Skema::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order')->orderBy('id');
    }
}
