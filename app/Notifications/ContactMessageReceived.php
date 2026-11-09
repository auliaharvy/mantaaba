<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactMessage $contactMessage)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $m = $this->contactMessage;

        return (new MailMessage)
            ->subject("[Hubungi Kami] {$m->subject} — {$m->name}")
            ->replyTo($m->email, $m->name)
            ->line("Pesan baru dari {$m->name} ({$m->email}" . ($m->city ? ", {$m->city}" : '') . ').')
            ->line($m->message)
            ->action('Buka di dashboard', route('contact-message.show', $m));
    }
}
