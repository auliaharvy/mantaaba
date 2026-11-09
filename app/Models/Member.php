<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use FlushesSiteCache, HasFactory, LogsActivity, SoftDeletes;

    public const TYPES = ['utama' => 'Utama', 'madya' => 'Madya', 'pratama' => 'Pratama', 'kehormatan' => 'Kehormatan'];

    public const STATUSES = ['aktif' => 'Aktif', 'skors' => 'Skors', 'berhenti' => 'Berhenti'];

    public const CERTIFICATIONS = ['A' => 'A', 'B' => 'B', 'C' => 'C'];

    public const DUES_STATUSES = ['lunas' => 'Lunas', 'belum_lunas' => 'Belum lunas', 'menunggak' => 'Menunggak'];

    protected $fillable = [
        'organization_id', 'member_number', 'kip_number', 'membership_type', 'certification', 'brevet',
        'npwp', 'nik', 'office_name', 'joined_at', 'sk_number', 'status', 'notes',
    ];

    protected $casts = ['brevet' => 'array', 'joined_at' => 'date'];

    protected static function booted(): void
    {
        static::updated(function (Member $member) {
            if ($member->wasChanged('status')) {
                $member->syncUserRole();
            }
        });
        static::deleted(fn (Member $member) => $member->syncUserRole(true));
    }

    /** PRD 8.2: berhenti/dihapus -> role anggota dicabut (jadi umum bila tak punya role lain); aktif lagi -> anggota. */
    public function syncUserRole(bool $removed = false): void
    {
        $user = $this->user;
        $before = $user->getRoleNames()->all();

        if ($removed || $this->status === 'berhenti') {
            $user->removeRole('anggota');
            if ($user->roles()->doesntExist()) {
                $user->assignRole('umum');
            }
        } elseif (! $user->hasRole('anggota') && ($user->hasRole('umum') || $user->roles()->doesntExist())) {
            $user->removeRole('umum');
            $user->assignRole('anggota');
        }

        $after = $user->fresh()->getRoleNames()->all();
        if ($before !== $after) {
            ActivityLog::record('role_changed', $user, ['before' => ['roles' => $before], 'after' => ['roles' => $after]]);
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function dues()
    {
        return $this->hasMany(MemberDue::class)->orderByDesc('year');
    }

    /** Data yang boleh dilihat user ini (scope PC, PRD 4.3). */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $ids = $user->scopedOrganizationIds();

        return $ids === null ? $query : $query->whereIn('members.organization_id', $ids);
    }

    /**
     * Status iuran (PRD 8.3), dihitung, tidak disimpan:
     * lunas = ada pembayaran tahun berjalan; menunggak = tahun lalu belum dibayar dan sudah >= 1 Mei
     * (anggota yang baru bergabung tahun ini tidak dihitung menunggak); selain itu belum lunas.
     */
    public function getDuesStatusAttribute(): string
    {
        $year = now()->year;
        $years = $this->relationLoaded('dues') ? $this->dues->pluck('year') : $this->dues()->pluck('year');

        if ($years->contains($year)) {
            return 'lunas';
        }

        return self::overdueApplies() && ! $years->contains($year - 1) && ! $this->joinedThisYear() ? 'menunggak' : 'belum_lunas';
    }

    public function scopeDuesStatus(Builder $query, string $status): Builder
    {
        $year = now()->year;
        $paid = fn ($y) => fn ($q) => $q->where('year', $y);

        return match ($status) {
            'lunas' => $query->whereHas('dues', $paid($year)),
            'menunggak' => self::overdueApplies() ? $this->overdue($query) : $query->whereRaw('1 = 0'),
            'belum_lunas' => $query->whereDoesntHave('dues', $paid($year))
                ->unless(! self::overdueApplies(), fn ($q) => $q->whereNotIn('members.id', $this->overdue(Member::query())->select('members.id'))),
            default => $query,
        };
    }

    private function overdue(Builder $query): Builder
    {
        $year = now()->year;

        return $query->whereDoesntHave('dues', fn ($q) => $q->whereIn('year', [$year, $year - 1]))
            ->where(fn ($q) => $q->whereNull('joined_at')->orWhereYear('joined_at', '<', $year));
    }

    private static function overdueApplies(): bool
    {
        return now()->gte(now()->startOfYear()->setMonth(5));
    }

    private function joinedThisYear(): bool
    {
        return $this->joined_at && $this->joined_at->year >= now()->year;
    }
}
