<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Kamar asrama (PRD 9.9). Penghuni = members.room_id. */
class Room extends Model
{
    public const GENDERS = ['L' => 'Putra', 'P' => 'Putri'];

    protected $fillable = ['code', 'name', 'building', 'capacity', 'gender', 'notes'];

    public function residents()
    {
        return $this->hasMany(Member::class)->orderBy('name');
    }
}
