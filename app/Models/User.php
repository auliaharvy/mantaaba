<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements \Illuminate\Contracts\Auth\MustVerifyEmail
{
    use HasRoles, HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'gender',
        'birthplace',
        'date_of_birth',

        'phone_number',
        'email',
        'email_verified_at',
        'password',
        'address',
        'photo',

        'organization_id',
        'is_active',
        'notify_forum_replies',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'notify_forum_replies' => 'boolean',
    ];

    /** Email verifikasi lewat antrean (PRD 8.10). */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\VerifyEmailQueued);
    }

    /** Role yang boleh ditetapkan user ini ke orang lain (B4: hanya SA yang memegang SA & admin PD). */
    public function assignableRoles(): array
    {
        $roles = array_keys(config('akp2i.roles'));

        return $this->hasRole('super_administrator')
            ? $roles
            : array_values(array_diff($roles, ['super_administrator', 'admin_pd']));
    }

    /** B4: admin non-SA tidak boleh menyentuh akun SA atau admin PD. */
    public function canManage(User $target): bool
    {
        return $this->hasRole('super_administrator')
            || ! $target->hasAnyRole(['super_administrator', 'admin_pd']);
    }

    /**
     * Organisasi yang boleh dilihat user (PRD 4.3 "scope PC").
     * null = semua (SA/admin PD); admin PC = PC-nya; lainnya = tidak ada.
     */
    public function scopedOrganizationIds(): ?array
    {
        if ($this->hasAnyRole(['super_administrator', 'admin_pd'])) {
            return null;
        }

        return $this->hasRole('admin_pc') && $this->organization_id ? [$this->organization_id] : [];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function member()
    {
        return $this->hasOne(Member::class);
    }

    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }

    public function threads()
    {
        return $this->hasMany(Thread::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }
    /** Rekap jam PPL/SKP (PRD 8.9): jumlah `skp_hours` event yang dihadiri, per tahun event. */
    public function pplHoursByYear(): \Illuminate\Support\Collection
    {
        return $this->participants()->where('participants.status', 'verified')->whereNotNull('attended_at')
            ->join('events', 'events.id', '=', 'participants.event_id')->whereNull('events.deleted_at')
            ->selectRaw('YEAR(events.starts_at) as year, SUM(COALESCE(events.skp_hours, 0)) as hours')
            ->groupBy('year')->orderByDesc('year')->pluck('hours', 'year')->map(fn ($h) => (float) $h);
    }

    public function boardMembers()
    {
        return $this->hasMany(BoardMember::class);
    }

    /** Jabatan pada periode aktif, dikelompokkan per jenis organisasi (pd/pc). */
    public function activePositions()
    {
        return $this->boardMembers()
            ->whereHas('period', fn ($q) => $q->where('is_active', true))
            ->with('period.organization', 'position', 'division')
            ->get()
            ->groupBy(fn ($b) => $b->period->organization->type);
    }
}
