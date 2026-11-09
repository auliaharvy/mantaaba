<?php

namespace Tests\Feature\M1;

use App\Models\BoardPeriod;
use App\Models\Division;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Position;

/** PRD 8.4 */
class BoardTest extends M1TestCase
{
    public function test_core_positions_exist_without_division_and_division_has_level()
    {
        $this->assertEqualsCanonicalizing(
            ['Ketua', 'Wakil Ketua', 'Sekretaris', 'Wakil Sekretaris', 'Bendahara', 'Wakil Bendahara'],
            Position::whereNull('division_id')->pluck('name')->all()
        );

        $this->actingAs($this->adminPd())->post(route('division.store'), ['name' => 'Bidang Pendidikan & Pelatihan', 'level' => 'pc', 'sort_order' => 2])->assertSessionHasNoErrors();
        $this->assertSame('pc', Division::where('name', 'Bidang Pendidikan & Pelatihan')->value('level'));
        // jabatan inti tanpa divisi tetap unik
        $this->actingAs($this->adminPd())->post(route('position.store'), ['name' => 'Ketua'])->assertSessionHasErrors('name');
    }

    public function test_only_one_active_period_per_organization()
    {
        $pd = Organization::active();
        $old = $pd->activeBoardPeriod;

        $this->actingAs($this->adminPd())->post(route('board.store'), ['organization_id' => $pd->id, 'name' => '2029–2034', 'is_active' => 1])->assertSessionHasNoErrors();

        $this->assertFalse($old->fresh()->is_active);
        $this->assertSame(1, BoardPeriod::where('organization_id', $pd->id)->where('is_active', true)->count());
        $this->assertSame('2029–2034', $pd->fresh()->activeBoardPeriod->name);
    }

    public function test_board_members_sorted_division_then_position_then_member()
    {
        $period = BoardPeriod::create(['organization_id' => Organization::active()->id, 'name' => 'Uji urutan']);
        $late = Division::create(['name' => 'Bidang Z', 'level' => 'pd', 'sort_order' => 9]);
        $early = Division::create(['name' => 'Bidang A', 'level' => 'pd', 'sort_order' => 1]);
        $members = Member::factory()->count(4)->create();
        $add = fn ($i, $position, $division = null, $order = 0) => $period->boardMembers()->create([
            'user_id' => $members[$i]->user_id, 'position_id' => $position->id, 'division_id' => $division?->id, 'sort_order' => $order,
        ]);
        $add(0, Position::create(['name' => 'Koordinator', 'division_id' => $late->id]), $late);
        $add(1, Position::create(['name' => 'Anggota Bidang', 'division_id' => $early->id, 'sort_order' => 2]), $early);
        $add(2, Position::create(['name' => 'Koordinator', 'division_id' => $early->id, 'sort_order' => 1]), $early);
        $add(3, Position::where('name', 'Ketua')->whereNull('division_id')->first());

        $this->assertSame(
            ['Ketua', 'Bidang A/Koordinator', 'Bidang A/Anggota Bidang', 'Bidang Z/Koordinator'],
            $period->boardMembers()->with('position', 'division')->get()
                ->map(fn ($b) => ($b->division ? $b->division->name . '/' : '') . $b->position->name)->all()
        );
    }

    public function test_add_member_requires_anggota_and_duplicate_creates_copy()
    {
        $period = Organization::active()->activeBoardPeriod;
        $secretary = Position::where('name', 'Wakil Sekretaris')->first();

        $this->actingAs($this->adminPd())->post(route('board-member.store', $period), ['user_id' => $this->user('general@mail.com')->id, 'position_id' => $secretary->id])
            ->assertSessionHasErrors('user_id');

        $member = Member::factory()->create();
        $this->actingAs($this->adminPd())->post(route('board-member.store', $period), ['user_id' => $member->user_id, 'position_id' => $secretary->id])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->adminPd())->post(route('board-member.store', $period), ['user_id' => $member->user_id, 'position_id' => $secretary->id])
            ->assertSessionHasErrors('position_id');

        $count = $period->boardMembers()->count();
        $this->actingAs($this->adminPd())->post(route('board.duplicate', $period))->assertRedirect();
        $copy = BoardPeriod::latest('id')->first();
        $this->assertFalse($copy->is_active);
        $this->assertSame($count, $copy->boardMembers()->count());
        $this->assertTrue($period->fresh()->is_active);
    }

    public function test_profile_shows_active_pd_and_pc_positions()
    {
        $this->actingAs($this->user('member@mail.com'))->get(route('profile.index'))
            ->assertOk()
            ->assertSeeInOrder(['Jabatan PD', 'Ketua', 'Jabatan PC', 'Wakil Ketua']);
    }

    public function test_admin_pc_views_only_own_board_and_cannot_edit()
    {
        $own = $this->ownPc()->activeBoardPeriod;
        $pdPeriod = Organization::active()->activeBoardPeriod;

        $this->actingAs($this->adminPc())->get(route('board.show', $own))->assertOk();
        $this->actingAs($this->adminPc())->get(route('board.show', $pdPeriod))->assertForbidden();
        $this->actingAs($this->adminPc())->get(route('board.edit', $own))->assertForbidden();
        $this->actingAs($this->adminPc())->post(route('board-member.store', $own), ['user_id' => 1, 'position_id' => 1])->assertForbidden();
        $this->actingAs($this->user('editor@mail.com'))->get(route('board.index'))->assertForbidden();
    }
}
