<?php

namespace App\Console\Commands;

use App\Models\AssetVehicle;
use App\Notifications\AssetReminder;
use App\Services\AssetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/** Pengingat harian 07:00: pajak/STNK/KIR tepat H-30 & H-7, servis berbasis hari H-7 & H-0 (servis berbasis km dikirim saat pemakaian selesai). */
class AssetReminders extends Command
{
    protected $signature = 'assets:reminders';

    protected $description = 'Kirim pengingat jatuh tempo pajak/STNK/KIR/servis kendaraan';

    public function handle(): int
    {
        $sent = 0;
        foreach (AssetVehicle::with('asset.pic')->whereHas('asset', fn ($q) => $q->whereNull('disposed_at'))->get() as $vehicle) {
            $messages = [];
            foreach (['tax_due_at' => 'Pajak kendaraan', 'stnk_due_at' => 'STNK', 'kir_due_at' => 'KIR'] as $field => $label) {
                $days = $vehicle->{$field} ? (int) today()->diffInDays($vehicle->{$field}, false) : null;
                if (in_array($days, [30, 7], true)) {
                    $messages[] = "{$label} jatuh tempo {$vehicle->{$field}->translatedFormat('j M Y')} ({$days} hari lagi)";
                }
            }
            $serviceDate = $vehicle->nextService()['date'];
            $days = $serviceDate ? (int) today()->diffInDays($serviceDate, false) : null;
            if (in_array($days, [7, 0], true)) {
                $messages[] = $days ? "Servis berkala {$serviceDate->translatedFormat('j M Y')} ({$days} hari lagi)" : 'Servis berkala jatuh tempo hari ini';
            }
            foreach ($messages as $message) {
                Notification::send(AssetService::reminderRecipients($vehicle->asset), new AssetReminder($vehicle->asset, $message));
                $sent++;
            }
        }
        $this->info("{$sent} pengingat dikirim.");

        return self::SUCCESS;
    }
}
