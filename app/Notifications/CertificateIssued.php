<?php

namespace App\Notifications;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateIssued extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Certificate $certificate)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $event = $this->certificate->participant->event;

        return (new MailMessage)
            ->subject("Sertifikat terbit: {$event->title}")
            ->greeting('Halo, ' . $notifiable->name)
            ->line("Sertifikat Anda untuk \"{$event->title}\" sudah terbit (nomor {$this->certificate->number}).")
            ->action('Unduh sertifikat', route('my-training.show', $this->certificate->participant))
            ->line('Keaslian sertifikat dapat diperiksa di ' . route('site.certificate', $this->certificate->verification_code));
    }
}
