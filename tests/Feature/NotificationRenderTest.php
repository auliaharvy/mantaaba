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
        $participant = Participant::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'notes' => 'Bukti tidak jelas']);
        $certificate = Certificate::create(['participant_id' => $participant->id, 'number' => '001/SERT/X/RMS/2026', 'verification_code' => 'ABCDEFGHJKLM', 'issued_at' => now()]);

        $notifications = [
            new Notifications\AccountActivation('https://contoh.test/atur-password'),
            new Notifications\ContactMessageReceived(ContactMessage::create(['name' => 'A', 'email' => 'a@b.c', 'subject' => 'Lainnya', 'message' => 'Halo semua'])),
            new Notifications\CertificateIssued($certificate),
            new Notifications\EventAnnouncement($event, 'Info', "Paragraf satu.\n\nParagraf dua."),
        ];
        foreach (['pending', 'verified', 'rejected', 'cancelled'] as $status) {
            $notifications[] = new Notifications\RegistrationStatusChanged(tap($participant->replicate(['ticket_code']))->forceFill(['id' => $participant->id, 'status' => $status]));
        }

        foreach ($notifications as $notification) {
            $html = (string) $notification->toMail($user)->render();
            $this->assertStringContainsString('<html', $html, get_class($notification));
            $this->assertNotEmpty($notification->toMail($user)->subject, get_class($notification));
        }
    }
}
