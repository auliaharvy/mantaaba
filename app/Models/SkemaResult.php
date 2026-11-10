<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/** Nilai 4 dimensi per skema (PRD 9.7). Terkunci setelah sertifikat terbit. */
class SkemaResult extends Model
{
    use LogsActivity;

    public const DIMENSIONS = ['knowledge' => 'Knowledge', 'skill' => 'Skill', 'character' => 'Character', 'social_cohesion' => 'Social Cohesion'];

    protected $fillable = ['enrollment_id', 'knowledge', 'skill', 'character', 'social_cohesion', 'final_score', 'passed', 'predicate', 'notes', 'assessed_by', 'assessed_at', 'locked_at'];

    protected $casts = ['passed' => 'boolean', 'assessed_at' => 'datetime', 'locked_at' => 'datetime'];

    public function enrollment()
    {
        return $this->belongsTo(SkemaEnrollment::class, 'enrollment_id');
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    /** Sertifikat yang belum dicabut (terbit atau masih diproses PDF-nya). */
    public function activeCertificate()
    {
        return $this->hasOne(Certificate::class)->whereNull('revoked_at');
    }
}
