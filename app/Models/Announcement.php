<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Pengumuman dashboard (PRD 9.19). */
class Announcement extends Model
{
    public const AUDIENCES = ['semua' => 'Semua pengguna', 'pengurus' => 'Pengurus', 'siswa' => 'Siswa', 'angkatan' => 'Satu angkatan', 'alumni' => 'Alumni'];

    protected $fillable = ['title', 'body', 'audience', 'batch_id', 'is_pinned', 'published_at', 'expires_at', 'created_by'];

    protected $casts = ['is_pinned' => 'boolean', 'published_at' => 'datetime', 'expires_at' => 'datetime'];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Pengumuman aktif untuk satu pengguna sesuai sasaran. */
    public function scopeFor(Builder $query, User $user): Builder
    {
        $member = $user->member;
        $audiences = array_filter([
            'semua',
            $user->hasAnyRole(['super_admin', 'pengurus']) ? 'pengurus' : null,
            $member?->category === 'siswa' ? 'siswa' : null,
            $member?->category === 'alumni' ? 'alumni' : null,
        ]);

        return $query->whereNotNull('published_at')->where('published_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($q) => $q->whereIn('audience', $audiences)
                ->orWhere(fn ($b) => $b->where('audience', 'angkatan')->where('batch_id', $member?->batch_id ?? 0)))
            ->orderByDesc('is_pinned')->latest('published_at');
    }

    /** Penerima email pengumuman. */
    public function recipients()
    {
        $active = User::where('is_active', true);

        return match ($this->audience) {
            'pengurus' => $active->role(['super_admin', 'pengurus'])->get(),
            'siswa', 'alumni' => $active->whereHas('member', fn ($q) => $q->where('category', $this->audience)->where('status', '!=', 'dikeluarkan'))->get(),
            'angkatan' => $active->whereHas('member', fn ($q) => $q->where('batch_id', $this->batch_id))->get(),
            default => $active->get(),
        };
    }
}
