<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventAnnouncement extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Event $event, public string $subject, public string $message)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject("[{$this->event->title}] {$this->subject}")->greeting('Halo, ' . ($notifiable->name ?? 'Peserta'));
        foreach (preg_split('/\R{2,}/', trim($this->message)) as $paragraph) {
            $mail->line($paragraph);
        }

        return $mail->action('Lihat event', route('site.training.show', $this->event->slug));
    }
}
