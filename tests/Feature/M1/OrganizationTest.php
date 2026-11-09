<?php

namespace Tests\Feature\M1;

use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;

/** PRD 8.1, 8.10 (is_active, scope admin PC), 8.13 (log). */
class OrganizationTest extends M1TestCase
{
    public function test_seed_has_pusat_pd_and_sample_pcs()
    {
        $pd = Organization::active();
        $this->assertSame('pd', $pd->type);
        $this->assertSame('pusat', $pd->parent->type);
        $this->assertGreaterThan(0, $pd->children()->pc()->count());
    }

    public function test_admin_pd_can_crud_pc()
    {
        $this->actingAs($this->adminPd())
            ->post(route('organization.store'), ['name' => 'PC AKP2I Kota Sukabumi', 'code' => 'JABAR-SKB', 'city' => 'Kota Sukabumi', 'is_active' => 1])
            ->assertSessionHasNoErrors();
        $pc = Organization::where('code', 'JABAR-SKB')->firstOrFail();
        $this->assertSame('pc', $pc->type);
        $this->assertSame(Organization::active()->id, $pc->parent_id);

        $this->actingAs($this->adminPd())->patch(route('organization.update', $pc), ['name' => 'PC AKP2I Sukabumi', 'code' => 'JABAR-SKB'])->assertSessionHasNoErrors();
        $this->assertFalse($pc->fresh()->is_active);

        $this->actingAs($this->adminPd())->delete(route('organization.destroy', $pc))->assertNoContent();
        $this->assertSoftDeleted($pc);
    }

    public function test_pc_with_members_cannot_be_deleted_only_deactivated()
    {
        $pc = $this->ownPc();
        $this->assertTrue($pc->members()->exists());

        $this->actingAs($this->adminPd())->deleteJson(route('organization.destroy', $pc))->assertStatus(422);
        $this->assertNotSoftDeleted($pc);
    }

    public function test_admin_pc_only_sees_own_pc_and_cannot_change_it()
    {
        $pcUser = $this->adminPc();

        $this->actingAs($pcUser)->get(route('organization.show', $this->ownPc()))->assertOk();
        $this->actingAs($pcUser)->get(route('organization.show', $this->otherPc()))->assertForbidden();
        $this->actingAs($pcUser)->get(route('organization.edit', $this->ownPc()))->assertForbidden();
        $this->actingAs($pcUser)->get(route('organization.create'))->assertForbidden();

        $rows = $this->actingAs($pcUser)->dt('organization.datatable', [], ['DT_RowIndex', 'name']);
        $this->assertSame([$this->ownPc()->name], array_column($rows['data'], 'name'));
    }

    public function test_pc_list_shows_active_member_count()
    {
        $pc = $this->ownPc();
        $expected = $pc->members()->where('status', 'aktif')->count();

        $rows = $this->actingAs($this->adminPd())->dt('organization.datatable', [], ['DT_RowIndex', 'name']);
        $row = collect($rows['data'])->firstWhere('id', $pc->id);
        $this->assertSame($expected, $row['members_count']);
    }

    public function test_inactive_user_cannot_log_in()
    {
        $user = $this->user('member@mail.com');
        $user->update(['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_scoped_organization_ids()
    {
        $this->assertNull($this->adminPd()->scopedOrganizationIds());
        $this->assertSame([$this->ownPc()->id], $this->adminPc()->scopedOrganizationIds());
        $this->assertSame([], $this->user('member@mail.com')->scopedOrganizationIds());
    }

    public function test_moving_member_to_another_pc_is_logged_and_log_page_is_restricted()
    {
        $member = Member::first();
        $from = $member->organization_id;
        $this->actingAs($this->adminPd());
        $member->update(['organization_id' => $this->otherPc()->id]);

        $log = ActivityLog::where('subject_type', Member::class)->where('subject_id', $member->id)->latest('id')->firstOrFail();
        $this->assertSame('updated', $log->action);
        $this->assertSame($from, $log->changes['before']['organization_id']);
        $this->assertSame($this->adminPd()->id, $log->user_id);

        $this->actingAs($this->adminPd())->get(route('activity-log.index'))->assertOk();
        $this->actingAs($this->user('editor@mail.com'))->get(route('activity-log.index'))->assertForbidden();
        $this->actingAs($this->adminPc())->get(route('activity-log.index'))->assertForbidden();
        $this->actingAs($this->adminPd())->dt('activity-log.datatable', [], ['DT_RowIndex', 'created_at', 'user.name']);
    }

    public function test_admin_pc_account_must_be_bound_to_a_pc()
    {
        $target = User::factory()->create()->assignRole('anggota');
        $payload = [
            'name' => $target->name, 'gender' => 'L', 'birthplace' => 'Bandung', 'date_of_birth' => '1990-01-01',
            'address' => 'Bandung', 'phone_number' => '0812', 'email' => $target->email, 'role' => 'admin_pc',
        ];

        $this->actingAs($this->adminPd())->patch(route('member.update', $target), $payload)->assertSessionHasErrors('organization_id');
        $this->actingAs($this->adminPd())->patch(route('member.update', $target), $payload + ['organization_id' => $this->otherPc()->id])->assertSessionHasNoErrors();

        $this->assertSame($this->otherPc()->id, $target->fresh()->organization_id);
        $this->assertTrue($target->fresh()->hasRole('admin_pc'));
    }
}
