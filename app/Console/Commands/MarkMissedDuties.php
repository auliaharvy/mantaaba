<?php

namespace App\Console\Commands;

use App\Models\CoachingNote;
use App\Models\Duty;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** PRD 9.9: 23.00 — piket terjadwal hari ini yang belum selesai → terlewat + catatan pembinaan otomatis. */
class MarkMissedDuties extends Command
{
    protected $signature = 'duties:mark-missed';

    protected $description = 'Tandai piket yang tidak dikerjakan sebagai terlewat dan buat catatan pembinaan';

    public function handle(): int
    {
        $count = 0;
        Duty::with('area')->where('status', 'terjadwal')->whereDate('date', '<=', today())->each(function (Duty $duty) use (&$count) {
            DB::transaction(function () use ($duty) {
                $duty->update(['status' => 'terlewat']);
                CoachingNote::create([
                    'member_id' => $duty->member_id, 'author_id' => null, 'date' => $duty->date, 'type' => 'perhatian', 'dimension' => 'tanggung_jawab',
                    'body' => "Piket {$duty->area->name} (" . Duty::SHIFTS[$duty->shift] . ") tanggal {$duty->date->translatedFormat('j M Y')} terlewat (otomatis).",
                    'visibility' => 'anggota',
                ]);
            });
            $count++;
        });
        $this->info("{$count} piket ditandai terlewat.");

        return self::SUCCESS;
    }
}
