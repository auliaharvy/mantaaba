<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Area piket + ceklis tugas (PRD 9.9). */
class DutyArea extends Model
{
    protected $fillable = ['name', 'description', 'checklist', 'is_active'];

    protected $casts = ['checklist' => 'array', 'is_active' => 'boolean'];

    public function duties()
    {
        return $this->hasMany(Duty::class);
    }
}
