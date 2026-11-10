<?php

namespace App\Notifications;

use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** PRD 9.8: tiket QR agenda publik lewat email (tautan ke halaman tiket). */
class EventTicket extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Participant $participant, public string $ticketUrl)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $event = $this->participant->event;

        return (new MailMessage)->subject("Tiket: {$event->title}")
            ->greeting('Halo, ' . $this->participant->name())
            ->line("Pendaftaran Anda untuk \"{$event->title}\" berhasil.")
            ->line('Waktu: ' . $event->starts_at->translatedFormat('l, j F Y H:i') . ($event->location ? " · {$event->location}" : ''))
            ->line("Kode tiket: {$this->participant->ticket_code}")
            ->action('Buka tiket (QR)', $this->ticketUrl)
            ->line('Tunjukkan QR tiket kepada panitia saat datang.');
    }
}
