<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** Periode buku bulanan (PRD 9.13.4). */
class FiscalPeriod extends Model
{
    use LogsActivity;

    protected $fillable = ['year', 'month', 'status', 'closed_by', 'closed_at', 'reopened_by', 'reopen_reason'];

    protected $casts = ['closed_at' => 'datetime'];

    protected $attributes = ['status' => 'open'];

    /** Periode untuk tanggal (dibuat terbuka bila belum ada). */
    public static function forDate($date): self
    {
        $date = Carbon::parse($date);

        return static::firstOrCreate(['year' => $date->year, 'month' => $date->month]);
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function label(): string
    {
        return Carbon::create($this->year, $this->month)->translatedFormat('F Y');
    }
}
