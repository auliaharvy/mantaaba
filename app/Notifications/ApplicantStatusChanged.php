<?php

namespace App\Notifications;

use App\Models\Applicant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** PRD 9.20: status pendaftaran berubah → email pendaftar (bila ada); pengurus juga mendapat tautan WA. */
class ApplicantStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Applicant $applicant)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject('Status pendaftaran ' . config('app.name'))
            ->greeting("Halo, {$this->applicant->name}")
            ->line(Applicant::PUBLIC_STATUS[$this->applicant->status])
            ->line("Nomor pendaftaran: {$this->applicant->registration_number}")
            ->action('Cek status', route('site.admission.status'));
    }
}
