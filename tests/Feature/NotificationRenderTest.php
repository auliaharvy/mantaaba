<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use App\Notifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Semua email (PRD 9.20) benar-benar bisa dirender — test lain memakai Notification::fake sehingga isi email tidak dieksekusi. */
class NotificationRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_mail_notification_renders()
    {
        $this->seed();
        $user = User::where('email', 'anggota@mail.com')->first();
        $event = Event::factory()->create();
        $participant = Participant::factory()->create(['event_id' => $event->id, 'guest_email' => 'tamu@contoh.id']);
        $member = $user->member;
        $certificate = Certificate::create(['member_id' => $member->id, 'series' => 'INK', 'year' => 2026, 'sequence' => 1, 'number' => '001/INK/RMS/XI/2026', 'verification_code' => 'ABCDEFGHJKLM', 'issued_at' => now()]);
        $applicant = \App\Models\Applicant::firstOrFail();
        $progress = \App\Models\LessonProgress::whereNotNull('submitted_at')->firstOrFail();
        $progress->update(['score' => 88, 'feedback' => 'Bagus', 'reviewed_at' => now()]);

        $notifications = [
            new Notifications\AccountActivation('https://contoh.test/atur-password'),
            new Notifications\ContactMessageReceived(ContactMessage::create(['name' => 'A', 'email' => 'a@b.c', 'subject' => 'Lainnya', 'message' => 'Halo semua'])),
            new Notifications\CertificateIssued($certificate),
            new Notifications\EventAnnouncement($event, 'Info', "Paragraf satu.\n\nParagraf dua."),
            new Notifications\NewApplicant($applicant),
            new Notifications\ApplicantStatusChanged($applicant),
            new Notifications\SeriousViolation($member),
            new Notifications\AnnouncementPublished(\App\Models\Announcement::firstOrFail()),
            new Notifications\AssignmentReviewed($progress),
        ];
        $notifications[] = new Notifications\EventTicket($participant, 'https://contoh.test/tiket');
        $notifications[] = new Notifications\AssetReminder(\App\Models\Asset::firstOrFail(), 'Pajak kendaraan jatuh tempo (30 hari lagi)');
        $notifications[] = new Notifications\DonationThanks(\App\Models\Donation::firstOrFail(), 'https://contoh.test/bukti');

        foreach ($notifications as $notification) {
            $html = (string) $notification->toMail($user)->render();
            $this->assertStringContainsString('<html', $html, get_class($notification));
            $this->assertNotEmpty($notification->toMail($user)->subject, get_class($notification));
        }
    }
}
