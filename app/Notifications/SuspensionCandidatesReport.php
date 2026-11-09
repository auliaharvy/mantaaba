<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuspensionCandidatesReport extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $count)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Usulan skors: {$this->count} anggota menunggak iuran")
            ->line("Ada {$this->count} anggota aktif yang menunggak iuran tahunan.")
            ->line('Status tidak berubah otomatis. Silakan tinjau dan konfirmasi di dashboard.')
            ->action('Tinjau usulan skors', route('membership.suspensions'));
    }
}
