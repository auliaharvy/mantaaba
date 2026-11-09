<?php

namespace App\Notifications;

use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** PRD 8.9 alur 4: email setiap perubahan status pendaftaran. */
class RegistrationStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Participant $participant)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $p = $this->participant;
        $title = $p->event->title;
        $mail = (new MailMessage)->greeting('Halo, ' . $notifiable->name);

        return (match ($p->status) {
            'verified' => $mail->subject("Pendaftaran terverifikasi: {$title}")
                ->line("Pendaftaran Anda untuk \"{$title}\" telah terverifikasi.")
                ->line('Tiket (QR) dan tautan acara online dapat dilihat di dashboard.'),
            'rejected' => $mail->subject("Pendaftaran ditolak: {$title}")
                ->line("Mohon maaf, pendaftaran Anda untuk \"{$title}\" ditolak.")
                ->line('Alasan: ' . ($p->notes ?: '-')),
            'cancelled' => $mail->subject("Pendaftaran dibatalkan: {$title}")
                ->line("Pendaftaran Anda untuk \"{$title}\" dibatalkan."),
            default => $mail->subject("Pendaftaran diterima: {$title}")
                ->line("Pendaftaran Anda untuk \"{$title}\" sudah kami terima.")
                ->line('Biaya: Rp' . number_format($p->amount, 0, ',', '.') . '. Silakan transfer lalu unggah bukti pembayaran di dashboard.'),
        })->action('Lihat pendaftaran', route('my-training.show', $p));
    }
}
