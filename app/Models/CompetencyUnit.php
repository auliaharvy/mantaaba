<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Unit kompetensi (kompetensi kurikulum): deskripsi, penilaian 4 dimensi, konteks praktik; elemennya menjadi modul pembelajaran. */
class CompetencyUnit extends Model
{
    protected $fillable = ['skema_id', 'code', 'title', 'description', 'assessment', 'context', 'sort_order'];

    protected $casts = ['assessment' => 'array', 'context' => 'array'];

    public function skema()
    {
        return $this->belongsTo(Skema::class);
    }

    public function modules()
    {
        return $this->hasMany(Module::class)->orderBy('sort_order')->orderBy('id');
    }
}
