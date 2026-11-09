<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public const SUBJECTS = ['Pendaftaran', 'Donasi', 'Kemitraan', 'Kunjungan', 'Lainnya'];

    protected $fillable = ['name', 'email', 'phone', 'city', 'subject', 'message', 'ip', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
