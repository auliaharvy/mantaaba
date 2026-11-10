<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class FarmCycle extends Model
{
    use LogsActivity;

    public const STATUSES = ['berjalan' => 'Berjalan', 'panen' => 'Panen', 'selesai' => 'Selesai', 'gagal' => 'Gagal'];

    protected $fillable = ['plot_id', 'commodity', 'variety', 'started_at', 'expected_harvest_at', 'initial_qty', 'initial_unit', 'target_yield', 'target_unit', 'notes'];

    protected $casts = ['started_at' => 'date', 'expected_harvest_at' => 'date', 'closed_at' => 'datetime', 'initial_qty' => 'decimal:2', 'target_yield' => 'decimal:2'];

    public function plot()
    {
        return $this->belongsTo(FarmPlot::class, 'plot_id');
    }

    public function logs()
    {
        return $this->hasMany(FarmLog::class, 'cycle_id')->latest('date')->latest('id');
    }

    public function harvests()
    {
        return $this->hasMany(Harvest::class, 'cycle_id')->latest('date')->latest('id');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['selesai', 'gagal'], true);
    }

    public function label(): string
    {
        return "{$this->commodity} · {$this->plot->code}";
    }

    /** Hari ke-n dari perkiraan total (untuk progres kartu). */
    public function progress(): ?array
    {
        if (! $this->expected_harvest_at) {
            return null;
        }
        $total = max(1, $this->started_at->diffInDays($this->expected_harvest_at));
        $day = min($total, max(0, (int) $this->started_at->diffInDays(today())));

        return ['day' => $day, 'total' => $total, 'percent' => (int) round($day / $total * 100)];
    }
}
