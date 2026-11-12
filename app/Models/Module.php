<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Modul pembelajaran = satu elemen dari unit kompetensi, beserta KUK-nya (PRD 9.6). Modul tanpa unit tetap boleh (skema lama/manual). */
class Module extends Model
{
    protected $fillable = ['skema_id', 'competency_unit_id', 'code', 'title', 'summary', 'criteria', 'sort_order'];

    protected $casts = ['criteria' => 'array'];

    public function unit()
    {
        return $this->belongsTo(CompetencyUnit::class, 'competency_unit_id');
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
