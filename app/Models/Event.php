<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Agenda (PRD 9.8): kajian, Sedulur Selawase, kelas, kegiatan umum, open house. */
class Event extends Model
{
    use FlushesSiteCache, HasFactory, HasSlug, SoftDeletes;

    public const TYPES = ['kajian_tajug' => 'Kajian Tajug', 'sedulur_selawase' => 'Sedulur Selawase', 'kelas' => 'Kelas', 'kegiatan_umum' => 'Kegiatan umum', 'open_house' => 'Open house', 'lainnya' => 'Lainnya'];

    public const MODES = ['offline' => 'Tatap muka', 'online' => 'Online', 'hybrid' => 'Hybrid'];

    public const STATUSES = ['draft' => 'Draf', 'open' => 'Dibuka', 'closed' => 'Pendaftaran ditutup', 'done' => 'Selesai'];

    /** publik = siapa pun mendaftar lewat situs; anggota/angkatan = peserta otomatis. */
    public const AUDIENCES = ['publik' => 'Umum (daftar di situs)', 'anggota' => 'Semua anggota', 'angkatan' => 'Satu angkatan'];

    protected $fillable = ['title', 'slug', 'type', 'mode', 'body', 'thumbnail', 'location', 'meeting_url', 'starts_at', 'ends_at',
        'registration_opens_at', 'registration_closes_at', 'quota', 'audience', 'batch_id', 'skema_id', 'status'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'registration_opens_at' => 'datetime', 'registration_closes_at' => 'datetime'];

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function skema()
    {
        return $this->belongsTo(Skema::class);
    }

    /** Terlihat di situs: bukan draf. Agenda internal (anggota/angkatan) tetap tampil sebagai informasi. */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('events.status', '!=', 'draft');
    }

    public function seatsTaken(): int
    {
        return $this->participants()->where('status', '!=', 'batal')->count();
    }

    public function seatsLeft(): ?int
    {
        return $this->quota === null ? null : max(0, $this->quota - $this->seatsTaken());
    }

    public function registrationOpen(): bool
    {
        return $this->audience === 'publik' && $this->status === 'open'
            && (! $this->registration_opens_at || now()->gte($this->registration_opens_at))
            && (! $this->registration_closes_at || now()->lte($this->registration_closes_at))
            && $this->ends_at->isFuture();
    }

    /** Pengguna anggota yang menjadi sasaran agenda internal. */
    public function targetUsers()
    {
        return User::where('is_active', true)->whereHas('member', fn ($q) => $q->where('status', 'aktif')
            ->when($this->audience === 'angkatan', fn ($m) => $m->where('batch_id', $this->batch_id)));
    }

    /** Agenda anggota/angkatan: peserta otomatis semua sasaran (tanpa daftar). */
    public function syncParticipants(): int
    {
        if (! in_array($this->audience, ['anggota', 'angkatan'], true) || $this->status === 'draft') {
            return 0;
        }
        $existing = $this->participants()->whereNotNull('user_id')->pluck('user_id');
        $added = 0;
        $this->targetUsers()->whereNotIn('id', $existing)->each(function (User $user) use (&$added) {
            $this->participants()->create(['user_id' => $user->id]);
            $added++;
        });

        return $added;
    }
}
