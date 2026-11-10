<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Peserta agenda (PRD 9.8): pengguna terdaftar atau tamu publik (nama, WA, email). */
class Participant extends Model
{
    use HasFactory, LogsActivity;

    public const STATUSES = ['terdaftar' => 'Terdaftar', 'hadir' => 'Hadir', 'batal' => 'Batal'];

    protected array $logExcept = ['guest_phone', 'guest_email'];

    protected $fillable = ['event_id', 'user_id', 'guest_name', 'guest_phone', 'guest_email', 'status', 'attended_at', 'notes'];

    protected $casts = ['attended_at' => 'datetime'];

    protected $attributes = ['status' => 'terdaftar'];

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

    public function name(): string
    {
        return $this->user?->name ?? (string) $this->guest_name;
    }

    public function phone(): ?string
    {
        return $this->user ? ($this->user->member?->phone ?? $this->user->phone_number) : $this->guest_phone;
    }

    public function email(): ?string
    {
        return $this->user?->email ?? $this->guest_email;
    }

    public function markPresent(): void
    {
        $this->update(['status' => 'hadir', 'attended_at' => $this->attended_at ?? now()]);
    }
}
