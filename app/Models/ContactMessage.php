<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public const SUBJECTS = ['Keanggotaan', 'Pelatihan', 'Iuran', 'Informasi Cabang', 'Lainnya'];

    protected $fillable = ['name', 'email', 'phone', 'city', 'subject', 'message', 'ip', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
