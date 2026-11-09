<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** PRD 8.7: email ke pemilik thread saat ada balasan (bisa dimatikan di profil). */
class ForumReplyPosted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Comment $comment)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $thread = $this->comment->thread;

        return (new MailMessage)
            ->subject("Balasan baru di \"{$thread->title}\"")
            ->line("{$this->comment->user->name} membalas diskusi Anda:")
            ->line('"' . Str::limit($this->comment->body, 300) . '"')
            ->action('Lihat diskusi', route('site.forum.show', $thread->slug) . '#komentar-' . $this->comment->id)
            ->line('Notifikasi ini bisa dimatikan di Profil → Tulisan & Notifikasi.');
    }
}
