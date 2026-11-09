<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

/** Email "Aktivasi akun" berisi link set password (PRD 8.2). Token dibuat saat dikirim. */
class MemberActivation extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $token = Password::broker()->createToken($notifiable);
        $url = route('password.reset', ['token' => $token, 'email' => $notifiable->email]);
        $hours = (int) round(config('auth.passwords.users.expire') / 60);

        return (new MailMessage)
            ->subject('Aktivasi Akun Anggota ' . config('app.name'))
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Data keanggotaan Anda telah dicatat oleh sekretariat PD AKP2I Jawa Barat.')
            ->line('Silakan buat password untuk mengaktifkan akun Anda dan mengakses profil, status iuran, serta sertifikat pelatihan.')
            ->action('Aktifkan Akun', $url)
            ->line("Tautan berlaku {$hours} jam. Bila kedaluwarsa, gunakan menu \"Lupa Password\" di halaman masuk dengan email ini.")
            ->salutation('Salam, Sekretariat PD AKP2I Jawa Barat');
    }
}
