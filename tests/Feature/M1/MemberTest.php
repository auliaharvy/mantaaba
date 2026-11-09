<?php

namespace Tests\Feature\M1;

use App\Models\Member;
use App\Models\User;
use App\Notifications\MemberActivation;
use Illuminate\Support\Facades\Notification;

/** PRD 8.2 */
class MemberTest extends M1TestCase
{
    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'Siti Rahma', 'email' => 'siti@contoh.id', 'phone_number' => '0812999',
            'organization_id' => $this->ownPc()->id, 'member_number' => 'AKP2I-777777', 'membership_type' => 'madya',
            'certification' => 'A', 'brevet' => ['A', 'B'], 'nik' => '3273012345678901', 'status' => 'aktif',
            'send_activation' => 1,
        ], $override);
    }

    public function test_admin_pc_gets_403_on_create_and_edit()
    {
        $member = $this->ownPc()->members()->first();

        $this->actingAs($this->adminPc())->get(route('membership.create'))->assertForbidden();
        $this->actingAs($this->adminPc())->post(route('membership.store'), $this->payload())->assertForbidden();
        $this->actingAs($this->adminPc())->get(route('membership.edit', $member))->assertForbidden();
        $this->actingAs($this->adminPc())->patch(route('membership.update', $member), $this->payload())->assertForbidden();
        $this->actingAs($this->adminPc())->delete(route('membership.destroy', $member))->assertForbidden();
    }

    public function test_admin_pc_reads_only_own_pc_members_without_nik()
    {
        $own = $this->ownPc()->members()->first();
        $own->update(['nik' => '3273999999999999']);
        $other = Member::factory()->create(['organization_id' => $this->otherPc()->id]);

        $this->actingAs($this->adminPc())->get(route('membership.show', $own))->assertOk()->assertDontSee('3273999999999999');
        $this->actingAs($this->adminPc())->get(route('membership.show', $other))->assertForbidden();

        $rows = $this->actingAs($this->adminPc())->dt('membership.datatable');
        $this->assertEqualsCanonicalizing($this->ownPc()->members()->pluck('id')->all(), array_column($rows['data'], 'id'));

        $this->actingAs($this->adminPd())->get(route('membership.show', $own))->assertSee('3273999999999999');
    }

    public function test_admin_pd_creates_member_and_activation_lets_them_log_in()
    {
        Notification::fake();

        $this->actingAs($this->adminPd())->post(route('membership.store'), $this->payload())->assertSessionHasNoErrors();

        $user = User::where('email', 'siti@contoh.id')->firstOrFail();
        $this->assertTrue($user->hasRole('anggota'));
        $this->assertSame('AKP2I-777777', $user->member->member_number);
        $this->assertSame(['A', 'B'], $user->member->brevet);

        $url = null;
        Notification::assertSentTo($user, MemberActivation::class, function ($notification) use ($user, &$url) {
            $url = $notification->toMail($user)->actionUrl;

            return true;
        });

        // ikuti link aktivasi: set password lalu login
        preg_match('#reset-password/([^?]+)#', $url, $m);
        $this->post('/logout');
        $this->post('/reset-password', ['token' => $m[1], 'email' => $user->email, 'password' => 'rahasia-baru-1', 'password_confirmation' => 'rahasia-baru-1'])
            ->assertSessionHasNoErrors();
        $this->post('/login', ['email' => $user->email, 'password' => 'rahasia-baru-1'])->assertRedirect(route('home'));
    }

    public function test_activation_can_be_postponed_and_sent_later()
    {
        Notification::fake();
        $this->actingAs($this->adminPd())->post(route('membership.store'), $this->payload(['send_activation' => 0]));
        $member = Member::where('member_number', 'AKP2I-777777')->firstOrFail();
        Notification::assertNothingSent();

        $this->actingAs($this->adminPd())->post(route('membership.activation', $member))->assertSessionHas('success');
        Notification::assertSentTo($member->user, MemberActivation::class);
    }

    public function test_existing_umum_account_is_upgraded_not_duplicated()
    {
        Notification::fake();
        $umum = $this->user('general@mail.com');
        $users = User::count();

        $this->actingAs($this->adminPd())->post(route('membership.store'), $this->payload(['email' => $umum->email]))->assertSessionHasNoErrors();

        $this->assertSame($users, User::count());
        $this->assertTrue($umum->fresh()->hasRole('anggota'));
        $this->assertFalse($umum->fresh()->hasRole('umum'));
        $this->assertNotNull($umum->fresh()->member);
    }

    public function test_duplicate_member_number_and_member_email_rejected()
    {
        $existing = Member::first();

        $this->actingAs($this->adminPd())->post(route('membership.store'), $this->payload(['member_number' => $existing->member_number]))
            ->assertSessionHasErrors('member_number');
        $this->actingAs($this->adminPd())->post(route('membership.store'), $this->payload(['email' => $existing->user->email, 'member_number' => 'X-1']))
            ->assertSessionHasErrors('email');
    }

    public function test_status_berhenti_downgrades_role_and_reactivation_restores_it()
    {
        $member = Member::factory()->create();
        $user = $member->user;
        $data = $this->payload(['email' => $user->email, 'name' => $user->name, 'member_number' => $member->member_number]);

        $this->actingAs($this->adminPd())->patch(route('membership.update', $member), ['status' => 'berhenti'] + $data)->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->hasRole('umum'));
        $this->assertFalse($user->fresh()->hasRole('anggota'));

        $this->actingAs($this->adminPd())->patch(route('membership.update', $member), ['status' => 'aktif'] + $data)->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->hasRole('anggota'));
    }

    public function test_member_detail_shows_identity_pc_type_dues_and_positions()
    {
        $member = $this->user('member@mail.com')->member;

        $this->actingAs($this->adminPd())->get(route('membership.show', $member))
            ->assertOk()
            ->assertSee($member->member_number)
            ->assertSee($member->organization->name)
            ->assertSee('Utama')
            ->assertSee('Iuran')
            ->assertSee('Jabatan PD')
            ->assertSee('Ketua')
            ->assertSee('Jabatan PC')
            ->assertSee('Riwayat Event');
    }

    public function test_member_datatable_filters_and_export_follow_filters()
    {
        $pc = $this->ownPc();
        $rows = $this->actingAs($this->adminPd())->dt('membership.datatable', ['organization_id' => $pc->id, 'status' => 'aktif']);
        $this->assertSame($pc->members()->where('status', 'aktif')->count(), $rows['recordsFiltered']);

        // cari & urut per kolom tidak error (B12)
        foreach ([1, 2] as $col) {
            $this->actingAs($this->adminPd())->dt('membership.datatable', ['search' => ['value' => 'a'], 'order' => [['column' => $col, 'dir' => 'desc']]]);
        }

        $csv = $this->actingAs($this->adminPd())->get(route('membership.export', ['organization_id' => $pc->id]))->assertOk()->streamedContent();
        $lines = array_filter(explode("\n", trim($csv)));
        $this->assertSame($pc->members()->count() + 1, count($lines));
        $this->assertStringStartsWith('nama,email,telepon,nomor_anggota', $lines[0]);
        $this->assertStringNotContainsString('nik', $lines[0]);
    }
}
