<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BoardPeriod extends Model
{
    use FlushesSiteCache, HasFactory, LogsActivity;

    protected $fillable = ['organization_id', 'name', 'starts_at', 'ends_at', 'is_active', 'sk_number', 'sk_file'];

    protected $casts = ['starts_at' => 'date', 'ends_at' => 'date', 'is_active' => 'boolean'];

    protected static function booted(): void
    {
        // PRD 8.4: hanya satu periode aktif per organisasi
        static::saved(function (BoardPeriod $period) {
            if ($period->is_active && ($period->wasChanged('is_active') || $period->wasRecentlyCreated)) {
                static::where('organization_id', $period->organization_id)->whereKeyNot($period->id)
                    ->where('is_active', true)->get()->each->update(['is_active' => false]);
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /** Urut divisi -> jabatan -> anggota (PRD 8.4 AC); jabatan inti (tanpa divisi) di depan. */
    public function boardMembers()
    {
        return $this->hasMany(BoardMember::class)
            ->select('board_members.*')
            ->leftJoin('divisions', 'divisions.id', '=', 'board_members.division_id')
            ->join('positions', 'positions.id', '=', 'board_members.position_id')
            ->orderByRaw('board_members.division_id IS NOT NULL')
            ->orderBy('divisions.sort_order')
            ->orderBy('positions.sort_order')
            ->orderBy('board_members.sort_order');
    }

    /** "Buat periode baru dari periode ini" (PRD 8.4). */
    public function duplicate(): self
    {
        return DB::transaction(function () {
            $copy = $this->replicate(['is_active', 'sk_file']);
            $copy->name = $this->name . ' (salinan)';
            $copy->is_active = false;
            $copy->save();
            foreach ($this->boardMembers()->get() as $member) {
                $copy->boardMembers()->create($member->only(['user_id', 'division_id', 'position_id', 'sort_order']));
            }

            return $copy;
        });
    }
}
