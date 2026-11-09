<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Test regresi bug lama + perbaikan teknis PRD 3.4 (T4). */
class M0BugFixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function profilePayload(array $override = []): array
    {
        return array_merge([
            'name' => 'Nama Baru',
            'gender' => 'L',
            'birthplace' => 'Bandung',
            'date_of_birth' => '1990-01-01',
            'address' => 'Jl. Asia Afrika',
            'phone_number' => '0812',
            'email' => 'baru@mail.com',
        ], $override);
    }

    public function test_b1_profile_update_only_changes_logged_in_user()
    {
        $member = $this->user('anggota@mail.com');
        $victim = $this->user('superadmin@mail.com');

        $this->actingAs($member)->patch('/dashboard/profil', $this->profilePayload())->assertRedirect(route('profile.index'));

        $this->assertSame('baru@mail.com', $member->fresh()->email);
        $this->assertSame('superadmin@mail.com', $victim->fresh()->email);
    }

    public function test_b1_old_profile_urls_with_user_id_are_gone()
    {
        $member = $this->user('anggota@mail.com');
        $victim = $this->user('superadmin@mail.com');

        $this->actingAs($member)->patch("/dashboard/profile/{$victim->id}", $this->profilePayload())->assertNotFound();
        $this->actingAs($member)->patch("/dashboard/profile/password/{$victim->id}", ['password' => 'hacked123', 'password_confirmation' => 'hacked123'])->assertNotFound();
        $this->actingAs($member)->patch('/dashboard/profil/password', ['password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])->assertSessionHasNoErrors();

        $this->assertTrue(password_verify('password', $victim->fresh()->password));
        $this->assertTrue(password_verify('rahasia123', $member->fresh()->password));
    }

    public function test_t4_manual_certificate_is_private_and_replaced()
    {
        Storage::fake('local');
        Storage::fake('public');
        $participant = Participant::first();
        $admin = $this->user('superadmin@mail.com');

        $this->actingAs($admin)->patch(route('participant.manual-certificate', $participant), ['certificate' => UploadedFile::fake()->create('sertifikat.pdf', 10, 'application/pdf')])
            ->assertSessionHasNoErrors();
        $old = $participant->fresh()->certificate;
        $this->actingAs($admin)->patch(route('participant.manual-certificate', $participant), ['certificate' => UploadedFile::fake()->create('sertifikat.pdf', 10, 'application/pdf')]);
        $new = $participant->fresh()->certificate;

        $this->assertNotSame($old, $new);
        $this->assertStringNotContainsString('sertifikat.pdf', $new);
        Storage::disk('local')->assertExists($new);
        Storage::disk('local')->assertMissing($old);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_b11_participant_cannot_register_twice_for_same_event()
    {
        $participant = Participant::first();

        $this->actingAs($this->user('superadmin@mail.com'))->post(route('event.participants.store', $participant->event_id), ['user_id' => $participant->user_id])
            ->assertSessionHasErrors('user_id');

        $this->assertSame(1, Participant::where('event_id', $participant->event_id)->where('user_id', $participant->user_id)->count());
    }

    public function test_t4_manual_certificate_download_only_for_owner_and_agenda_managers()
    {
        Storage::fake('local');
        $member = $this->user('anggota@mail.com');
        $other = User::factory()->create()->assignRole('anggota');
        Storage::disk('local')->put('participant/certificate/abc.pdf', 'PDF');
        $participant = Participant::factory()->create(['event_id' => Event::factory()->create()->id, 'user_id' => $member->id, 'certificate' => 'participant/certificate/abc.pdf']);

        $this->actingAs($member)->get(route('my-training.show', $participant))->assertOk()->assertSee(route('participant.manual-certificate.download', $participant), false);
        $this->actingAs($member)->get(route('participant.manual-certificate.download', $participant))->assertOk();
        $this->actingAs($other)->get(route('participant.manual-certificate.download', $participant))->assertForbidden();
        $this->actingAs($this->user('pengurus@mail.com'))->get(route('participant.manual-certificate.download', $participant))->assertOk();
    }

    public function test_b8_dashboard_home_works_without_events()
    {
        Event::query()->delete();

        $this->actingAs($this->user('superadmin@mail.com'))->get('/dashboard')->assertOk()->assertSee('Belum ada agenda mendatang');
    }

    public function test_b9_root_and_logout_do_not_404()
    {
        $this->get('/')->assertOk(); // M2: beranda publik menggantikan redirect sementara
        $this->actingAs($this->user('anggota@mail.com'))->post('/logout')->assertRedirect('/');
    }
}
