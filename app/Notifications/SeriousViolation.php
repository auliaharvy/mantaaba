<?php

namespace App\Notifications;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** PRD 9.5: 3 pelanggaran sedang / 1 berat dalam 30 hari → Ketua (Super Admin & pemegang member-update). */
class SeriousViolation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Member $member)
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject("Perhatian pembinaan: {$this->member->name}")
            ->line("{$this->member->name} ({$this->member->member_number}) mencapai batas pelanggaran 30 hari terakhir. Mohon ditindaklanjuti.")
            ->action('Lihat anggota', route('member.show', $this->member) . '?tab=pembinaan');
    }

    public function toArray($notifiable): array
    {
        return ['title' => 'Perhatian pembinaan', 'body' => "{$this->member->name} mencapai batas pelanggaran 30 hari", 'url' => route('member.show', $this->member) . '?tab=pembinaan'];
    }
}
