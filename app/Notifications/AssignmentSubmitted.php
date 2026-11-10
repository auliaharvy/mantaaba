<?php

namespace App\Notifications;

use App\Models\LessonProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** PRD 9.20: tugas dikumpulkan → mentor (lonceng). */
class AssignmentSubmitted extends Notification
{
    use Queueable;

    public function __construct(public LessonProgress $progress)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return ['title' => 'Tugas masuk', 'body' => "{$this->progress->user->name} · {$this->progress->lesson->title}", 'url' => route('submission.show', $this->progress)];
    }
}
