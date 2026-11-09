<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/** Email verifikasi akun umum (PRD 8.10), lewat antrean & berbahasa Indonesia. */
class VerifyEmailQueued extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifikasi Email Akun ' . config('app.name'))
            ->line('Terima kasih telah mendaftar. Klik tombol di bawah untuk memverifikasi alamat email Anda.')
            ->action('Verifikasi Email', $url)
            ->line('Abaikan email ini bila Anda tidak merasa mendaftar.');
    }
}
