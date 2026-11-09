<?php

namespace Database\Seeders;

use App\Models\BoardPeriod;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Data demo M1: anggota + iuran + kepengurusan PD & satu PC. */
class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $pc = Organization::pc()->orderBy('id')->first(); // PC milik akun demo adminpc@mail.com
        $year = now()->year;

        $demo = Member::factory()->create([
            'user_id' => User::where('email', 'member@mail.com')->value('id'),
            'organization_id' => $pc->id,
            'member_number' => 'AKP2I-000001',
            'membership_type' => 'utama',
        ]);
        $demo->dues()->createMany([
            ['year' => $year - 1, 'amount' => 500000, 'paid_at' => now()->subYear()],
            ['year' => $year, 'amount' => 500000, 'paid_at' => now()->startOfYear()->addDays(10)],
        ]);

        $members = Member::factory()->count(20)->create();
        // sebagian lunas, sebagian tidak bayar sama sekali (-> menunggak setelah 1 Mei)
        $members->take(12)->each(fn ($m) => $m->dues()->create(['year' => $year - 1, 'amount' => 500000, 'paid_at' => now()->subYear()]));
        $members->take(6)->each(fn ($m) => $m->dues()->create(['year' => $year, 'amount' => 500000, 'paid_at' => now()->startOfYear()]));

        $core = Position::whereNull('division_id')->pluck('id', 'name');
        $pd = BoardPeriod::create(['organization_id' => Organization::active()->id, 'name' => '2024–2029', 'starts_at' => '2024-01-01', 'ends_at' => '2029-12-31', 'is_active' => true]);
        $pd->boardMembers()->create(['user_id' => $demo->user_id, 'position_id' => $core['Ketua']]);
        $pd->boardMembers()->create(['user_id' => $members[0]->user_id, 'position_id' => $core['Sekretaris']]);
        $pd->boardMembers()->create(['user_id' => $members[1]->user_id, 'position_id' => $core['Bendahara']]);

        $pcPeriod = BoardPeriod::create(['organization_id' => $pc->id, 'name' => '2023–2028', 'starts_at' => '2023-01-01', 'ends_at' => '2028-12-31', 'is_active' => true]);
        $pcPeriod->boardMembers()->create(['user_id' => $demo->user_id, 'position_id' => $core['Wakil Ketua']]);
    }
}
