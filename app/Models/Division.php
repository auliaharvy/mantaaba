<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    public const LEVELS = ['pd' => 'Pengurus Daerah', 'pc' => 'Pengurus Cabang'];

    protected $fillable = ['name', 'level', 'sort_order'];

    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function boardMembers()
    {
        return $this->hasMany(BoardMember::class);
    }
}
