<?php

namespace App\Notifications;

use App\Models\LessonProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** PRD 9.20: tugas dinilai → siswa. */
class AssignmentReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LessonProgress $progress)
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject('Tugas dinilai: ' . $this->progress->lesson->title)
            ->greeting("Halo, {$notifiable->name}")
            ->line("Tugas \"{$this->progress->lesson->title}\" sudah dinilai: {$this->progress->score}.")
            ->line('Umpan balik: ' . $this->progress->feedback)
            ->action('Lihat tugas', route('learn.lesson', $this->progress->lesson_id));
    }

    public function toArray($notifiable): array
    {
        return ['title' => 'Tugas dinilai', 'body' => "{$this->progress->lesson->title} · nilai {$this->progress->score}", 'url' => route('learn.lesson', $this->progress->lesson_id)];
    }
}
