<?php

namespace App\Notifications;

use App\Models\Blog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BlogAwaitingReview extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Blog $blog)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tulisan menunggu review: ' . $this->blog->title)
            ->line("{$this->blog->user->name} mengirim tulisan \"{$this->blog->title}\" untuk ditinjau.")
            ->action('Tinjau tulisan', route('blog.show', $this->blog));
    }
}
