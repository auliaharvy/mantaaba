<?php

namespace App\Notifications;

use App\Models\Asset;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** PRD 9.17/9.20: pengingat pajak/STNK/KIR/servis → PIC aset & koordinator (lonceng + email). */
class AssetReminder extends Notification
{
    use Queueable;

    public function __construct(public Asset $asset, public string $message)
    {
    }

    public function via($notifiable): array
    {
        return $notifiable->email ? ['database', 'mail'] : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject("Pengingat aset: {$this->asset->name}")
            ->line("{$this->asset->code} · {$this->asset->name}" . ($this->asset->vehicle?->plate_number ? " ({$this->asset->vehicle->plate_number})" : ''))
            ->line($this->message)->action('Buka aset', route('asset.show', $this->asset));
    }

    public function toArray($notifiable): array
    {
        return ['title' => "Pengingat: {$this->asset->name}", 'body' => $this->message, 'url' => route('asset.show', $this->asset)];
    }
}
