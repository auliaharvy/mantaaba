<?php

namespace Tests\Feature;

use App\Models\BoardMember;
use App\Models\Blog;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\Demo\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Data demo (docs/DEMO.md) terbentuk lengkap dan saling nyambung. */
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_builds_coherent_data()
    {
        Storage::fake('public');
        Storage::fake('local');
        DemoSeeder::$withCertificatePdfs = false;
        $this->seed(DemoSeeder::class);

        $this->assertSame(12, Organization::pc()->count());
        $this->assertSame(0, Organization::pc()->where(fn ($q) => $q->whereNull('profile')->orWhereNull('greeting'))->count(), 'setiap PC punya profil & sambutan');
        $this->assertSame(150, Member::count());
        $this->assertGreaterThan(0, Member::duesStatus('menunggak')->count());
        $this->assertSame(0, User::where('email', 'not like', '%@%example.%')->count(), 'semua email di domain contoh');
        $this->assertSame(0, User::where('name', 'like', 'Anggota%')->orWhere('name', 'like', 'Admin %')->count(), 'nama orang realistis');

        // pengurus selalu anggota (atau admin PD/SA)
        $this->assertSame(0, BoardMember::whereDoesntHave('user', fn ($q) => $q->role(['anggota', 'admin_pd', 'super_administrator']))->count());
        $this->assertNotNull(Organization::active()->activeBoardPeriod);

        // event selesai punya peserta hadir; event dibuka punya pendaftar pending dengan bukti bayar
        $this->assertGreaterThan(0, Participant::whereNotNull('attended_at')->count());
        $this->assertGreaterThan(0, Participant::where('status', 'pending')->whereNotNull('payment_proof')->count());
        Event::all()->each(fn ($e) => Storage::disk('public')->assertExists($e->thumbnail));
        $this->assertSame(Event::count(), Event::distinct()->count('code'));

        $this->assertGreaterThanOrEqual(12, Blog::published()->count());
        Gallery::with('photos')->get()->each(fn ($g) => $this->assertGreaterThan(3, $g->photos->count()));

        foreach (['superadmin@example.com', 'sekretariat.pd@example.com', 'humas@example.com', 'adminpc.bandung@example.com', 'anggota@example.com', 'umum@example.com'] as $email) {
            $this->assertTrue(User::where('email', $email)->exists(), $email);
        }
        $this->assertTrue(User::where('email', 'adminpc.bandung@example.com')->first()->hasRole('admin_pc'));

        $this->get('/')->assertOk();
        $this->get('/pengurus-cabang/' . Organization::where('code', 'JABAR-BDG')->value('slug'))->assertOk()->assertSee('Sambutan Ketua');
    }
}
