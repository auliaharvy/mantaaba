<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'division_id', 'sort_order'];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function boardMembers()
    {
        return $this->hasMany(BoardMember::class);
    }
}
