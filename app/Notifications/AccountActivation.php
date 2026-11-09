<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email berisi tautan atur password (PRD 9.18). Tautan dibuat pemanggil agar sama dengan tautan WhatsApp. */
class AccountActivation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $url) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $hours = (int) round(config('auth.passwords.users.expire') / 60);

        return (new MailMessage)
            ->subject('Atur Password Akun ' . config('app.name'))
            ->greeting("Assalamu'alaikum, " . $notifiable->name)
            ->line('Akun Anda di sistem ' . config('app.name') . ' telah disiapkan oleh pengurus.')
            ->line('Silakan buat password untuk mulai menggunakan akun Anda.')
            ->action('Atur Password', $this->url)
            ->line("Tautan berlaku {$hours} jam. Bila kedaluwarsa, gunakan menu \"Lupa Password\" di halaman masuk.")
            ->salutation('Salam, Pengurus ' . config('app.name'));
    }
}
