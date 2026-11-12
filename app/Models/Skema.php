<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use Illuminate\Database\Eloquent\Model;

/** Skema kompetensi (PRD 9.6): FND, SIM, SIA, SIP, IT. */
class Skema extends Model
{
    use FlushesSiteCache; // /kurikulum & blok skemas

    protected $fillable = ['code', 'name', 'title', 'standard', 'month_label', 'description', 'objectives', 'character_matrix', 'pass_score', 'certificate_template', 'certificate_signatories', 'is_public', 'sort_order'];

    protected $casts = ['standard' => 'array', 'character_matrix' => 'array', 'certificate_signatories' => 'array', 'is_public' => 'boolean'];

    public function units()
    {
        return $this->hasMany(CompetencyUnit::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Jumlah KUK seluruh modul (elemen). */
    public function criteriaCount(): int
    {
        return $this->modules->sum(fn ($m) => count($m->criteria ?? []));
    }

    public function modules()
    {
        return $this->hasMany(Module::class)->orderBy('sort_order')->orderBy('id');
    }

    public function lessons()
    {
        return $this->hasManyThrough(Lesson::class, Module::class);
    }

    public function enrollments()
    {
        return $this->hasMany(SkemaEnrollment::class);
    }

    public function passScore(): int
    {
        return $this->pass_score ?? Setting::current()->default_pass_score;
    }

    public static function ordered()
    {
        return static::orderBy('sort_order')->orderBy('id');
    }
}
