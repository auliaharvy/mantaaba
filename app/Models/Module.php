<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Modul dalam skema (PRD 9.6). */
class Module extends Model
{
    protected $fillable = ['skema_id', 'code', 'title', 'summary', 'sort_order'];

    public function skema()
    {
        return $this->belongsTo(Skema::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order')->orderBy('id');
    }
}
