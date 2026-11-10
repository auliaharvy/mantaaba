<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Jadwal piket satu anggota pada satu area & shift (PRD 9.9). */
class Duty extends Model
{
    public const SHIFTS = ['pagi' => 'Pagi', 'siang' => 'Siang', 'sore' => 'Sore', 'malam' => 'Malam'];

    public const STATUSES = ['terjadwal' => 'Terjadwal', 'selesai' => 'Selesai', 'diverifikasi' => 'Diverifikasi', 'terlewat' => 'Terlewat'];

    public const DONE = ['selesai', 'diverifikasi'];

    protected $fillable = ['duty_area_id', 'date', 'shift', 'member_id', 'status', 'checklist_done', 'done_at', 'verified_by', 'verified_at', 'note', 'photo'];

    protected $casts = ['date' => 'date', 'checklist_done' => 'array', 'done_at' => 'datetime', 'verified_at' => 'datetime'];

    public function area()
    {
        return $this->belongsTo(DutyArea::class, 'duty_area_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** Persentase piket selesai (selesai/diverifikasi) dari piket yang sudah lewat waktunya, per anggota. */
    public static function completionRate(int $memberId, $from, $to): ?int
    {
        $rows = static::where('member_id', $memberId)->whereBetween('date', [$from, $to])
            ->where(fn ($q) => $q->where('status', '!=', 'terjadwal')->orWhere('date', '<', today()))->pluck('status');

        return $rows->isEmpty() ? null : (int) round($rows->filter(fn ($s) => in_array($s, self::DONE, true))->count() / $rows->count() * 100);
    }
}
