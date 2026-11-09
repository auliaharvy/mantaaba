<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use FlushesSiteCache, HasFactory, HasSlug, SoftDeletes;

    public const TYPES = ['ppl' => 'PPL', 'brevet_a' => 'Brevet A', 'brevet_b' => 'Brevet B', 'brevet_c' => 'Brevet C', 'seminar' => 'Seminar', 'webinar' => 'Webinar', 'lainnya' => 'Lainnya'];

    public const MODES = ['offline' => 'Tatap muka', 'online' => 'Online', 'hybrid' => 'Hybrid'];

    public const STATUSES = ['draft' => 'Draf', 'open' => 'Pendaftaran dibuka', 'closed' => 'Pendaftaran ditutup', 'done' => 'Selesai'];

    public const AUDIENCES = ['publik' => 'Umum & anggota', 'anggota' => 'Khusus anggota'];

    protected $fillable = [
        'organization_id', 'title', 'slug', 'body', 'thumbnail', 'type', 'code', 'mode', 'location', 'meeting_url',
        'starts_at', 'ends_at', 'registration_opens_at', 'registration_closes_at', 'quota', 'audience',
        'fee_member', 'fee_public', 'skp_hours', 'status', 'certificate_template', 'certificate_signatory',
    ];

    protected $casts = [
        'starts_at' => 'datetime', 'ends_at' => 'datetime',
        'registration_opens_at' => 'datetime', 'registration_closes_at' => 'datetime',
        'fee_member' => 'decimal:2', 'fee_public' => 'decimal:2', 'skp_hours' => 'decimal:2',
        'certificate_signatory' => 'array',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    /** Terlihat publik: semua kecuali draf. */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('events.status', '!=', 'draft');
    }

    /** Scope PC (PRD 4.3): admin PC hanya event PC-nya. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $ids = $user->scopedOrganizationIds();

        return $ids === null ? $query : $query->whereIn('events.organization_id', $ids);
    }

    /** Peserta yang memakan kuota. */
    public function seatsTaken(): int
    {
        return $this->participants()->whereIn('status', ['pending', 'verified'])->count();
    }

    public function seatsLeft(): ?int
    {
        return $this->quota === null ? null : max(0, $this->quota - $this->seatsTaken());
    }

    public function registrationOpen(): bool
    {
        return $this->status === 'open'
            && (! $this->registration_opens_at || now()->gte($this->registration_opens_at))
            && (! $this->registration_closes_at || now()->lte($this->registration_closes_at));
    }

    /** Biaya untuk user ini (PRD 8.9): anggota aktif -> fee_member; selain itu (termasuk skors, Q7) -> fee_public. */
    public function feeFor(User $user): string
    {
        return $user->member?->status === 'aktif' && $user->hasRole('anggota') ? $this->fee_member : $this->fee_public;
    }

    public function isFree(): bool
    {
        return (float) $this->fee_member === 0.0 && (float) $this->fee_public === 0.0;
    }
}
