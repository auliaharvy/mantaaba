<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Participant extends Model
{
    use HasFactory, LogsActivity;

    public const STATUSES = ['pending' => 'Menunggu verifikasi', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'];

    protected $fillable = [
        'event_id', 'user_id', 'status', 'amount', 'payment_proof', 'verified_by', 'verified_at', 'attended_at',
        'ticket_code', 'notes', 'reason', 'file', 'certificate',
    ];

    protected $casts = ['amount' => 'decimal:2', 'verified_at' => 'datetime', 'attended_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (Participant $p) {
            do {
                $p->ticket_code = strtoupper(Str::random(10));
            } while (static::where('ticket_code', $p->ticket_code)->exists());
        });
    }

    public function event()
    {
        return $this->belongsTo(Event::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
