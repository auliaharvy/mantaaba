<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Certificate;
use App\Models\Comment;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Thread;
use App\Models\User;
use App\Notifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Semua email (8.13) benar-benar bisa dirender — test lain memakai Notification::fake sehingga isi email tidak dieksekusi. */
class NotificationRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_mail_notification_renders()
    {
        $this->seed();
        $user = User::where('email', 'member@mail.com')->first();
        $event = Event::factory()->create();
        $participant = Participant::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'notes' => 'Bukti tidak jelas']);
        $certificate = Certificate::create(['participant_id' => $participant->id, 'number' => '001/SERT/X/AKP2I-JABAR/2026', 'verification_code' => 'ABCDEFGHJKLM', 'issued_at' => now()]);
        $thread = Thread::factory()->create(['user_id' => $user->id]);
        $comment = Comment::create(['thread_id' => $thread->id, 'user_id' => $user->id, 'body' => 'Balasan']);

        $notifications = [
            new Notifications\MemberActivation,
            new Notifications\VerifyEmailQueued,
            new Notifications\SuspensionCandidatesReport(3),
            new Notifications\ContactMessageReceived(ContactMessage::create(['name' => 'A', 'email' => 'a@b.c', 'subject' => 'Lainnya', 'message' => 'Halo semua'])),
            new Notifications\BlogAwaitingReview(Blog::factory()->create(['user_id' => $user->id])),
            new Notifications\ForumReplyPosted($comment),
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
