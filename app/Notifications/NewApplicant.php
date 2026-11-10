<?php

namespace App\Notifications;

use App\Models\Applicant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** PRD 9.20: pendaftar baru → pemegang admission-view. */
class NewApplicant extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Applicant $applicant)
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject("Pendaftar baru: {$this->applicant->name}")
            ->line("Pendaftar baru {$this->applicant->name} ({$this->applicant->registration_number}) masuk untuk angkatan {$this->applicant->batch->code}.")
            ->action('Lihat pendaftar', route('applicant.show', $this->applicant));
    }

    public function toArray($notifiable): array
    {
        return ['title' => 'Pendaftar baru', 'body' => "{$this->applicant->name} · {$this->applicant->registration_number}" . ($this->applicant->is_priority ? ' · prioritas' : ''),
            'url' => route('applicant.show', $this->applicant)];
    }
}
