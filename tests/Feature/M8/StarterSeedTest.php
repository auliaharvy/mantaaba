<?php

namespace Tests\Feature\M8;

use App\Models\Blog;
use App\Models\Event;
use App\Models\FarmPlot;
use App\Models\Journal;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** MANTAABA_DEMO=false: hanya data inti, satu Super Admin dari .env, serta berita & agenda awal. */
class StarterSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_without_demo_has_only_admin_news_and_events()
    {
        Storage::fake('public');
        config(['mantaaba.demo' => false, 'mantaaba.admin_email' => 'superadmin@contoh.test', 'mantaaba.admin_password' => 'rahasia#2025']);

        $this->seed();

        $admin = User::sole();
        $this->assertSame('superadmin@contoh.test', $admin->email);
        $this->assertTrue($admin->hasRole('super_admin'));
        $this->assertTrue(Hash::check('rahasia#2025', $admin->password));
        $this->assertSame(0, Member::count());
        $this->assertSame(0, Journal::count());
        $this->assertSame(0, FarmPlot::count());

        $this->assertSame(7, Blog::published()->count());
        $this->assertTrue(Blog::whereNull('thumbnail')->doesntExist());
        Storage::disk('public')->assertExists(Blog::first()->thumbnail);
        $this->assertSame(4, Event::where('status', 'open')->where('starts_at', '>', now())->count());
        $this->assertSame(2, Event::where('status', 'done')->where('ends_at', '<', now())->count());

        $this->get('/')->assertOk()->assertSee('Pendaftaran Angkatan Baru');
        $this->get('/agenda')->assertOk()->assertSee('Open House Rumah Mantaaba Sigaling');

        // seeding ulang tidak menggandakan konten
        $this->seed(\Database\Seeders\StarterContentSeeder::class);
        $this->assertSame(7, Blog::count());
        $this->assertSame(6, Event::count());
    }
}
