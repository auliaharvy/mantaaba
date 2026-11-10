<?php

namespace App\Services;

use App\Models\Duty;
use App\Models\DutyArea;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generator rotasi piket (PRD 9.9): round-robin atas daftar anggota, tidak pernah menempatkan orang yang sama
 * dua kali pada hari yang sama (termasuk piket yang sudah ada di area lain). Slot yang tidak bisa diisi dilaporkan.
 */
class DutyGenerator
{
    /** @return array{created: int, short: array<int, string>} */
    public function generate(DutyArea $area, Carbon $from, Carbon $to, array $shifts, array $memberIds, int $perShift): array
    {
        $queue = array_values(array_unique(array_map('intval', $memberIds)));
        $count = count($queue);
        $pointer = 0;
        $created = 0;
        $short = [];

        DB::transaction(function () use ($area, $from, $to, $shifts, $queue, $count, $perShift, &$pointer, &$created, &$short) {
            foreach (CarbonPeriod::create($from, $to) as $date) {
                $busy = Duty::whereDate('date', $date)->pluck('member_id')->all();
                foreach ($shifts as $shift) {
                    $need = $perShift - Duty::where('duty_area_id', $area->id)->whereDate('date', $date)->where('shift', $shift)->count();
                    for ($tries = 0; $need > 0 && $tries < $count; $tries++) {
                        $memberId = $queue[$pointer++ % $count];
                        if (in_array($memberId, $busy, true)) {
                            continue;
                        }
                        Duty::create(['duty_area_id' => $area->id, 'date' => $date->toDateString(), 'shift' => $shift, 'member_id' => $memberId]);
                        $busy[] = $memberId;
                        $need--;
                        $created++;
                    }
                    if ($need > 0) {
                        $short[] = $date->translatedFormat('j M') . ' ' . Duty::SHIFTS[$shift] . " (kurang {$need})";
                    }
                }
            }
        });

        return ['created' => $created, 'short' => $short];
    }
}
