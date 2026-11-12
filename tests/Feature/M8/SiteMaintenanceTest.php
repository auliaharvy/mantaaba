<?php

namespace Tests\Feature\M8;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Mode "sedang dibangun": publik ditutup, login & dashboard tetap jalan untuk anggota dan pengurus. */
class SiteMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function form(array $override = []): array
    {
        $s = Setting::current();

        return array_merge(['site_name' => $s->site_name, 'foundation_name' => $s->foundation_name, 'color_primary' => '#1E4D3A', 'color_secondary' => '#C9A04A',
            'color_accent' => '#A84B24', 'color_surface' => '#F4F7F3', 'assessment_weights' => Setting::DEFAULT_WEIGHTS, 'default_pass_score' => 70, 'min_character_score' => 70], $override);
    }

    public function test_admin_toggles_mode_public_sees_construction_page_members_keep_working()
    {
        $admin = User::where('email', 'superadmin@mail.com')->firstOrFail();
        $anggota = User::where('email', 'anggota@mail.com')->firstOrFail();
        $this->get('/')->assertOk();

        $this->actingAs($admin)->patch(route('setting.update'), $this->form(['maintenance_mode' => 1, 'maintenance_title' => 'Segera Hadir',
            'maintenance_message' => 'Situs baru sedang disiapkan.', 'maintenance_until' => now()->addWeek()->toDateString()]))->assertSessionHasNoErrors();
        auth()->logout();

        foreach (['/', '/berita', '/agenda', '/kurikulum', '/pendaftaran', '/donasi', '/tentang'] as $url) {
            $this->get($url)->assertStatus(503)->assertSee('Segera Hadir')->assertSee('Situs baru sedang disiapkan.')->assertHeader('Retry-After');
        }
        $this->get('/login')->assertOk();

        // anggota & pengurus tetap bisa memakai dashboard, dan melihat situs dengan penanda mode aktif
        $this->actingAs($anggota)->get(route('home'))->assertOk();
        $this->actingAs($anggota)->get('/')->assertOk()->assertSee('Mode "Sedang Dibangun" aktif', false)->assertDontSee('tab=pemeliharaan', false);
        $this->actingAs($admin)->get('/')->assertOk()->assertSee('tab=pemeliharaan', false);

        // anggota tidak boleh mengubah setting
        $this->actingAs($anggota)->patch(route('setting.update'), $this->form(['maintenance_mode' => 0]))->assertForbidden();

        $this->actingAs($admin)->patch(route('setting.update'), $this->form(['maintenance_mode' => 0]))->assertSessionHasNoErrors();
        auth()->logout();
        $this->get('/')->assertOk()->assertDontSee('Segera Hadir');
    }
}
