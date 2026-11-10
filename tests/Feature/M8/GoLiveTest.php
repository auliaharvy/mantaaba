<?php

namespace Tests\Feature\M8;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\DashboardData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Dashboard per permission, smoke test publik per role, kelengkapan log & demo (PRD 9.19–9.20, 13.5, AC M8). */
class GoLiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(\Database\Seeders\DemoVolumeSeeder::class); // volume presentasi (dilewati DemoSeeder saat test)
    }

    public function test_dashboard_cards_follow_permissions()
    {
        $labels = fn (User $u) => array_column(DashboardData::cards($u), 0);

        $member = User::where('email', 'anggota@mail.com')->first();
        $this->assertSame([], $labels($member), 'anggota tanpa permission pengurus tidak melihat kartu');
        $this->assertSame([], DashboardData::charts($member));
        $this->actingAs($member)->get(route('overview.show', 'saya'))->assertOk()->assertSee('Progres belajar')->assertDontSee('Voucher menunggu persetujuan')->assertDontSee('chart-finance');
        $this->actingAs($member)->get(route('overview.show', 'keuangan'))->assertForbidden();

        $cook = User::factory()->create()->assignRole('pengurus')->givePackage('siswa_dapur');
        $this->assertContains('Biaya makan/orang hari ini', $labels($cook));
        $this->assertNotContains('Voucher menunggu persetujuan', $labels($cook));
        $this->assertNotContains('Saldo kas & bank', $labels($cook));
        $this->assertSame(['kitchen'], array_keys(DashboardData::charts($cook)));

        $treasurer = User::factory()->create()->assignRole('pengurus')->givePackage('bendahara');
        $this->assertContains('Voucher menunggu persetujuan', $labels($treasurer));
        $this->assertContains('Saldo kas & bank', $labels($treasurer));
        $this->assertNotContains('Biaya makan/orang hari ini', $labels($treasurer));
        $charts = DashboardData::charts($treasurer);
        $this->assertCount(12, $charts['finance']['labels']);
        $this->assertArrayHasKey('budget', $charts);
        $this->actingAs($treasurer)->get(route('overview.show', 'keuangan'))->assertOk()->assertSee('chart-finance')->assertSee('Penerimaan vs beban 12 bulan')->assertSee('Antrean Persetujuan');
        $this->assertSame([], DashboardData::cards($treasurer, 'dapur'), 'kartu modul lain tidak ikut');

        $admin = User::where('email', 'superadmin@mail.com')->first();
        $this->assertGreaterThanOrEqual(12, count($labels($admin)), 'Super Admin melihat semua kartu');
    }

    /** Beranda = ikon modul sesuai hak akses; sidebar accordion; ringkasan tiap modul tanpa error. */
    public function test_home_is_module_launcher_and_every_overview_renders()
    {
        $member = User::where('email', 'anggota@mail.com')->first();
        $this->actingAs($member)->get('/dashboard')->assertOk()->assertSee(route('overview.show', 'saya'))
            ->assertDontSee(route('overview.show', 'keuangan'))->assertDontSee('Voucher menunggu persetujuan');
        $this->actingAs($member)->get(route('overview.show', 'tidak-ada'))->assertNotFound();

        foreach (['superadmin@mail.com', 'pengurus@mail.com', 'anggota@mail.com'] as $email) {
            $user = User::where('email', $email)->first();
            $home = $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('menu-accordion', false);
            foreach (\App\Support\Menu::modules($user) as $key => $module) {
                $home->assertSee(route('overview.show', $key));
                $this->actingAs($user)->get(route('overview.show', $key))->assertOk()->assertSee($module['title']);
            }
        }
        $admin = User::where('email', 'superadmin@mail.com')->first();
        $this->assertCount(count(config('mantaaba.menu.modules')), \App\Support\Menu::modules($admin), 'Super Admin melihat semua modul');
    }

    /** Semua route GET publik (tanpa parameter) merespons < 500 untuk tamu & tiap role. */
    public function test_public_routes_respond_for_every_role()
    {
        $uris = collect(Route::getRoutes())->filter(fn ($r) => in_array('GET', $r->methods()) && ! Str::startsWith($r->uri(), ['dashboard', '_', 'api', 'sanctum', 'livewire', 'storage'])
            && ! Str::contains($r->uri(), '{') && ! in_array('signed', $r->gatherMiddleware()))->map(fn ($r) => '/' . ltrim($r->uri(), '/'))->unique()->values();
        $this->assertGreaterThan(10, $uris->count());
        foreach ([null, 'superadmin@mail.com', 'pengurus@mail.com', 'anggota@mail.com'] as $email) {
            if ($email) {
                $this->actingAs(User::where('email', $email)->first());
            }
            foreach ($uris as $uri) {
                $status = $this->get($uri)->getStatusCode();
                $this->assertLessThan(500, $status, "GET {$uri} sebagai " . ($email ?? 'tamu') . " -> {$status}");
            }
        }
    }

    public function test_settings_changes_are_logged_and_demo_volume_exists()
    {
        Setting::current()->update(['meal_budget_per_person_day' => 16000]);
        $this->assertTrue(ActivityLog::where('subject_type', (new Setting)->getMorphClass())->where('action', 'updated')->exists());

        // PRD 13.5: 12 siswa, ±40 voucher terposting, ±1 bulan dapur, aset, siklus kebun
        $this->assertSame(12, \App\Models\Member::where('category', 'siswa')->where('status', 'aktif')->count());
        $this->assertGreaterThanOrEqual(40, \App\Models\Journal::where('status', 'terposting')->count());
        $this->assertGreaterThanOrEqual(20, \App\Models\KitchenLog::count());
        $this->assertGreaterThanOrEqual(2, \App\Models\FarmCycle::count());
        $this->assertGreaterThanOrEqual(3, \App\Models\Asset::count());
        $trial = \App\Services\Ledger::trialBalance(today()->toDateString());
        $this->assertSame($trial->sum('debit'), $trial->sum('credit'), 'data demo tetap seimbang');
    }
}
