<?php

namespace Tests\Feature\M1;

use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\User;
use App\Notifications\SuspensionCandidatesReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/** PRD 8.3 */
class DuesTest extends M1TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function member(array $years, ?string $joined = '2015-01-01'): Member
    {
        $member = Member::factory()->create(['joined_at' => $joined]);
        foreach ($years as $year) {
            $member->dues()->create(['year' => $year, 'amount' => 500000, 'paid_at' => "{$year}-02-01"]);
        }

        return $member->fresh();
    }

    public function test_dues_status_rules()
    {
        Carbon::setTestNow('2026-06-01');
        $this->assertSame('lunas', $this->member([2026])->dues_status);
        $this->assertSame('belum_lunas', $this->member([2025])->dues_status);
        $this->assertSame('menunggak', $this->member([2024])->dues_status);
        $this->assertSame('belum_lunas', $this->member([], '2026-02-01')->dues_status, 'anggota baru tahun ini tidak menunggak');

        Carbon::setTestNow('2026-04-30');
        $this->assertSame('belum_lunas', $this->member([2024])->dues_status, 'sebelum 1 Mei belum menunggak');
    }

    public function test_dues_status_scope_matches_accessor()
    {
        Carbon::setTestNow('2026-06-01');
        $this->member([2026]);
        $this->member([2025]);
        $this->member([2024]);

        foreach (['lunas', 'belum_lunas', 'menunggak'] as $status) {
            Member::with('dues')->duesStatus($status)->get()->each(fn ($m) => $this->assertSame($status, $m->dues_status));
        }
        $this->assertSame(Member::count(), collect(['lunas', 'belum_lunas', 'menunggak'])->sum(fn ($s) => Member::duesStatus($s)->count()));
    }

    public function test_admin_pd_records_dues_with_private_proof()
    {
        Storage::fake('local');
        $member = $this->member([]);

        $this->actingAs($this->adminPd())->post(route('membership.dues.store', $member), [
            'year' => now()->year, 'amount' => 600000, 'paid_at' => now()->toDateString(),
            'proof_file' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertSessionHasNoErrors();

        $due = $member->dues()->firstOrFail();
        Storage::disk('local')->assertExists($due->proof_file);
        $this->assertStringStartsWith('dues/', $due->proof_file);
        $this->assertSame('lunas', $member->fresh()->dues_status);
        $this->assertTrue(ActivityLog::where('subject_type', $due->getMorphClass())->where('subject_id', $due->id)->exists());

        // duplikat tahun ditolak; admin PC tidak boleh mencatat
        $this->actingAs($this->adminPd())->post(route('membership.dues.store', $member), ['year' => now()->year, 'amount' => 1, 'paid_at' => now()->toDateString()])
            ->assertSessionHasErrors('year');
        $this->actingAs($this->adminPc())->post(route('membership.dues.store', $member), ['year' => 2020, 'amount' => 1, 'paid_at' => '2020-01-01'])
            ->assertForbidden();
    }

    public function test_proof_only_for_admin_pd_and_owner_and_member_sees_only_own_dues()
    {
        Storage::fake('local');
        $owner = $this->user('member@mail.com');
        $due = $owner->member->dues()->first();
        $due->update(['proof_file' => UploadedFile::fake()->image('b.jpg')->store('dues', 'local')]);
        $other = $this->member([2020]);

        $this->actingAs($owner)->get(route('dues.proof', $due))->assertOk();
        $this->actingAs($this->adminPd())->get(route('dues.proof', $due))->assertOk();
        $this->actingAs($other->user)->get(route('dues.proof', $due))->assertForbidden();
        $this->actingAs($this->adminPc())->get(route('dues.proof', $due))->assertForbidden();

        // anggota melihat riwayat iurannya di profil, tapi tidak bisa membuka data anggota lain
        $this->actingAs($owner)->get(route('profile.index'))->assertOk()->assertSee('Keanggotaan')->assertSee((string) $due->year)->assertSee('AKP2I-000001');
        $this->actingAs($owner)->get(route('membership.show', $other))->assertForbidden();
        $this->actingAs($owner)->get(route('membership.index'))->assertForbidden();
    }

    public function test_suspension_candidates_need_confirmation_and_daily_report()
    {
        Carbon::setTestNow('2026-06-01');
        Notification::fake();
        $overdue = $this->member([2024]);
        $paid = $this->member([2026]);

        $rows = $this->actingAs($this->adminPd())->dt('membership.suspensions.datatable');
        $ids = array_column($rows['data'], 'id');
        $this->assertContains($overdue->id, $ids);
        $this->assertNotContains($paid->id, $ids);
        $this->assertSame('aktif', $overdue->fresh()->status, 'tidak ada perubahan otomatis');

        $this->artisan('members:suspension-candidates')->assertSuccessful();
        Notification::assertSentTo(User::role('admin_pd')->get(), SuspensionCandidatesReport::class);
        $this->assertSame('aktif', $overdue->fresh()->status);

        $this->actingAs($this->adminPd())->patch(route('membership.suspend', $overdue))->assertSessionHas('success');
        $this->assertSame('skors', $overdue->fresh()->status);
        $this->assertTrue(ActivityLog::where('action', 'status_changed')->where('subject_id', $overdue->id)->exists());
        $this->actingAs($this->adminPc())->get(route('membership.suspensions'))->assertForbidden();
    }
}
