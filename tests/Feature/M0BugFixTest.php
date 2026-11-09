<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Test regresi bug PRD 3.3 yang tidak bergantung pada role baru. */
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
        $member = $this->user('member@mail.com');
        $victim = $this->user('superadmin@mail.com');

        $this->actingAs($member)->patch('/dashboard/profil', $this->profilePayload())->assertRedirect(route('profile.index'));

        $this->assertSame('baru@mail.com', $member->fresh()->email);
        $this->assertSame('superadmin@mail.com', $victim->fresh()->email);
    }

    public function test_b1_old_profile_urls_with_user_id_are_gone()
    {
        $member = $this->user('member@mail.com');
        $victim = $this->user('superadmin@mail.com');

        $this->actingAs($member)->patch("/dashboard/profile/{$victim->id}", $this->profilePayload())->assertNotFound();
        $this->actingAs($member)->patch("/dashboard/profile/password/{$victim->id}", ['password' => 'hacked123', 'password_confirm' => 'hacked123'])->assertNotFound();
        $this->actingAs($member)->patch('/dashboard/profil/password', ['password' => 'rahasia123', 'password_confirm' => 'rahasia123']);

        $this->assertTrue(password_verify('password', $victim->fresh()->password));
        $this->assertTrue(password_verify('rahasia123', $member->fresh()->password));
    }

    public function test_b6_b11_manual_certificate_file_is_replaced_with_random_name()
    {
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
        Storage::disk('public')->assertExists($new);
        Storage::disk('public')->assertMissing($old);
    }

    public function test_b11_participant_cannot_register_twice_for_same_event()
    {
        $participant = Participant::first();

        $this->actingAs($this->user('superadmin@mail.com'))->post(route('event.participants.store', $participant->event_id), ['user_id' => $participant->user_id])
            ->assertSessionHasErrors('user_id');

        $this->assertSame(1, Participant::where('event_id', $participant->event_id)->where('user_id', $participant->user_id)->count());
    }

    public function test_b7_certificate_link_points_to_stored_file()
    {
        $member = $this->user('member@mail.com');
        $participant = Participant::factory()->create(['event_id' => Event::factory()->create()->id, 'user_id' => $member->id, 'certificate' => 'participant/certificate/abc.png']);

        $this->actingAs($member)->get(route('my-training.show', $participant))->assertOk()->assertSee('/storage/participant/certificate/abc.png', false);
    }

    public function test_b8_dashboard_home_works_without_events()
    {
        Event::query()->delete();

        $this->actingAs($this->user('superadmin@mail.com'))->get('/dashboard')->assertOk()->assertSee('Belum ada acara');
    }

    public function test_b9_root_and_logout_do_not_404()
    {
        $this->get('/')->assertOk(); // M2: beranda publik menggantikan redirect sementara
        $this->actingAs($this->user('member@mail.com'))->post('/logout')->assertRedirect('/');
    }

    public function test_b10_position_name_unique_per_division()
    {
        $admin = $this->user('superadmin@mail.com');
        $a = Division::create(['name' => 'Bidang A']);
        $b = Division::create(['name' => 'Bidang B']);
        Position::create(['division_id' => $a->id, 'name' => 'Koordinator']);

        $this->actingAs($admin)->post(route('position.store'), ['division_id' => $b->id, 'name' => 'Koordinator'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('position.store'), ['division_id' => $a->id, 'name' => 'Koordinator'])->assertSessionHasErrors('name');

        $this->assertSame(['Koordinator'], $b->positions()->pluck('name')->all());
    }
}
