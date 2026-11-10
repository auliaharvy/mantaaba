<?php

namespace App\Notifications;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** PRD 9.20: sertifikat terbit → siswa. */
class CertificateIssued extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Certificate $certificate)
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Sertifikat terbit: ' . $this->certificate->title())
            ->greeting('Halo, ' . $notifiable->name)
            ->line("Selamat! Sertifikat {$this->certificate->title()} Anda sudah terbit (nomor {$this->certificate->number}).")
            ->action('Unduh sertifikat', route('certificate.index'))
            ->line('Keaslian sertifikat dapat diperiksa di ' . route('site.certificate', $this->certificate->verification_code));
    }

    public function toArray($notifiable): array
    {
        return ['title' => 'Sertifikat terbit', 'body' => $this->certificate->title() . ' · ' . $this->certificate->number, 'url' => route('certificate.index')];
    }
}
