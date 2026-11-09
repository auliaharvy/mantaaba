<?php

namespace Tests\Feature\M2;

use App\Models\BoardPeriod;
use App\Models\ContactMessage;
use App\Models\Document;
use App\Models\Gallery;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** PRD 7 & 8.5: situs publik + CMS */
class SiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
        RateLimiter::clear('contact');
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_every_public_page_renders()
    {
        $pc = Organization::pc()->first();
        $gallery = Gallery::create(['title' => 'Rapat Kerja', 'published_at' => now()]);
        $uris = ['/', '/profil', '/pengurus', '/pengurus-cabang', "/pengurus-cabang/{$pc->slug}", '/galeri', "/galeri/{$gallery->slug}",
            '/peraturan-organisasi', '/keanggotaan', '/keanggotaan/layanan', '/keanggotaan/cek', '/keanggotaan/cek?nomor=AKP2I-000001',
            '/kontak', '/kebijakan-privasi', '/syarat-ketentuan', '/pelatihan', '/pelatihan?tab=selesai', '/pelatihan/' . \App\Models\Event::value('slug'), '/berita', '/berita/' . \App\Models\Blog::published()->value('slug'), '/jurnal', '/jurnal/' . \App\Models\Research::value('slug'),
            '/forum', '/forum/' . \App\Models\Thread::value('slug')];

        foreach ($uris as $uri) {
            $this->get($uri)->assertOk()->assertSee('<meta property="og:title"', false);
        }
        $this->get('/halaman/tentang-keanggotaan')->assertRedirect(route('site.membership'));
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee(route('site.branch', $pc->slug), false);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: ' . route('site.sitemap'), false);
        $this->get('/galeri/tidak-ada')->assertNotFound();
    }

    public function test_editor_changes_tentang_keanggotaan_and_it_shows_immediately()
    {
        $page = Page::where('slug', 'tentang-keanggotaan')->firstOrFail();
        $this->get('/keanggotaan'); // isi cache

        $this->actingAs($this->user('editor@mail.com'))->patch(route('page.update', $page), [
            'title' => 'Tentang Keanggotaan', 'body' => '<p>Iuran tahunan dibayar paling lambat 31 Desember.</p><script>alert(1)</script>', 'is_published' => 1,
        ])->assertSessionHasNoErrors();

        $this->get('/keanggotaan')->assertOk()->assertSee('Iuran tahunan dibayar paling lambat 31 Desember.')->assertDontSee('alert(1)', false);
        $this->assertSame($this->user('editor@mail.com')->id, $page->fresh()->updated_by);
        $this->actingAs($this->user('member@mail.com'))->get(route('page.edit', $page))->assertForbidden();
    }

    public function test_fixed_pages_cannot_be_deleted()
    {
        $page = Page::first();
        $this->assertFalse($this->user('admin@mail.com')->can('delete', $page));
        $this->actingAs($this->user('superadmin@mail.com'))->delete("/dashboard/page/{$page->slug}")->assertStatus(405);
        $this->assertSame(6, Page::count());
    }

    public function test_member_service_url_from_setting_changes_every_button()
    {
        $setting = Setting::current();
        $setting->update(['url_member_service' => 'https://contoh.akp2i.id/layanan-baru/']);

        $this->get('/keanggotaan/layanan')->assertSee('https://contoh.akp2i.id/layanan-baru/', false);
        $this->get('/')->assertSee('https://contoh.akp2i.id/layanan-baru/', false); // footer
        $this->actingAs($this->user('member@mail.com'))->get(route('profile.index'))->assertSee('https://contoh.akp2i.id/layanan-baru/', false);
    }

    public function test_setting_form_updates_site_identity()
    {
        Storage::fake('public');
        $setting = Setting::current();

        $this->actingAs($this->user('admin@mail.com'))->patch(route('setting.update', $setting), [
            'site_name' => 'PD AKP2I Jabar', 'email' => 'pd@contoh.id', 'url_registration' => 'https://akp2i.id/daftar/',
            'url_member_service' => 'https://akp2i.id/pelayanan-anggota/', 'logo' => UploadedFile::fake()->image('logo.png', 400, 120),
            'dues_amounts' => ['utama' => 750000],
        ])->assertSessionHasNoErrors();

        $setting = Setting::current();
        Storage::disk('public')->assertExists($setting->logo);
        $this->assertSame(750000, $setting->dues_amounts['utama']);
        $this->get('/')->assertSee('PD AKP2I Jabar')->assertSee('https://akp2i.id/daftar/', false);
        $this->actingAs($this->user('editor@mail.com'))->get(route('setting.edit', $setting))->assertForbidden();
    }

    public function test_open_graph_preview_uses_page_title_and_image()
    {
        Storage::fake('public');
        $page = Page::where('slug', 'profil')->first();
        $this->actingAs($this->user('editor@mail.com'))->patch(route('page.update', $page), [
            'title' => 'Profil', 'meta_title' => 'Profil PD AKP2I Jawa Barat', 'meta_description' => 'Sejarah dan visi misi.',
            'cover' => UploadedFile::fake()->image('cover.jpg', 1200, 630), 'is_published' => 1,
        ])->assertSessionHasNoErrors();

        $html = $this->get('/profil')->assertOk()->getContent();
        $this->assertStringContainsString('<meta property="og:title" content="Profil PD AKP2I Jawa Barat">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="Sejarah dan visi misi.">', $html);
        $this->assertMatchesRegularExpression('#<meta property="og:image" content="[^"]+/storage/pages/[^"]+\.jpg">#', $html);
        $this->assertStringContainsString('<link rel="canonical" href="' . route('site.profile') . '">', $html);
    }

    public function test_branches_page_lists_active_pcs_with_active_member_count()
    {
        $inactive = Organization::factory()->create(['is_active' => false, 'name' => 'PC Nonaktif Uji']);
        $response = $this->get('/pengurus-cabang')->assertOk()->assertDontSee('PC Nonaktif Uji');

        foreach (Organization::pc()->where('is_active', true)->get() as $pc) {
            $response->assertSee($pc->name);
        }
        $pc = Organization::pc()->withCount(['members' => fn ($q) => $q->where('status', 'aktif')])->orderByDesc('members_count')->first();
        Member::factory()->create(['organization_id' => $pc->id, 'status' => 'skors']);
        Cache::flush();
        $this->assertStringContainsString('data-testid="active-members">' . $pc->members_count . '<', $this->get('/pengurus-cabang')->getContent());
        $this->get("/pengurus-cabang/{$inactive->slug}")->assertNotFound();
    }

    public function test_board_pages_sorted_and_only_active_period_without_pii()
    {
        $pd = Organization::active();
        $old = BoardPeriod::create(['organization_id' => $pd->id, 'name' => '2019–2024', 'starts_at' => '2019-01-01']);
        $old->boardMembers()->create(['user_id' => Member::factory()->create()->user_id, 'position_id' => Position::where('name', 'Ketua')->first()->id]);
        $ketua = $pd->activeBoardPeriod->boardMembers()->with('user')->get()->first()->user;

        $html = $this->get('/pengurus')->assertOk()->assertSeeInOrder(['Pengurus Inti', 'Ketua', 'Sekretaris', 'Bendahara'])
            ->assertDontSee($old->boardMembers()->first()->user->name)->getContent();
        $this->assertStringNotContainsString($ketua->email, $html);
        $this->assertStringNotContainsString((string) $ketua->phone_number, $html);

        $this->get("/pengurus?periode={$old->id}")->assertSee($old->boardMembers()->first()->user->name);
        $this->get('/pengurus-cabang/' . Organization::pc()->orderBy('id')->first()->slug)->assertSee('Wakil Ketua');
    }

    public function test_member_check_shows_only_public_fields_and_is_rate_limited()
    {
        RateLimiter::clear(sha1('member-check' . '127.0.0.1'));
        $member = Member::with('user')->where('member_number', 'AKP2I-000001')->first();
        $member->update(['nik' => '3273011111111111', 'npwp' => '11.111.111.1-111.000']);

        $this->get('/keanggotaan/cek?nomor=AKP2I-000001')->assertOk()
            ->assertSee($member->user->name)->assertSee('Utama')->assertSee($member->organization->name)->assertSee('Aktif')
            ->assertDontSee($member->user->email)->assertDontSee('3273011111111111')->assertDontSee('11.111.111.1-111.000');

        $member->update(['status' => 'berhenti']);
        $this->get('/keanggotaan/cek?nomor=AKP2I-000001')->assertSee('Tidak aktif');
        $this->get('/keanggotaan/cek?nomor=TIDAK-ADA')->assertSee('tidak ditemukan');

        for ($i = 0; $i < 10; $i++) {
            $this->get('/keanggotaan/cek?nomor=X' . $i);
        }
        $this->get('/keanggotaan/cek?nomor=Y')->assertStatus(429);
    }

    public function test_contact_form_honeypot_notification_and_throttle()
    {
        Notification::fake();
        $valid = ['name' => 'Budi', 'email' => 'budi@contoh.id', 'subject' => 'Keanggotaan', 'message' => 'Bagaimana cara pindah cabang?'];

        $this->post('/kontak', $valid + ['website' => 'http://spam.example'])->assertRedirect('/kontak');
        $this->assertSame(0, ContactMessage::count());

        $this->post('/kontak', ['name' => '', 'email' => 'x', 'subject' => 'Lainnya', 'message' => 'pendek'])->assertSessionHasErrors(['name', 'email', 'message']);

        $this->post('/kontak', $valid)->assertRedirect('/kontak')->assertSessionHas('success');
        $this->assertSame(1, ContactMessage::count());
        Notification::assertSentOnDemand(ContactMessageReceived::class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === Setting::current()->email);

        // sudah 3 kiriman (honeypot, tidak valid, valid); ke-4 & ke-5 lolos, ke-6 ditolak (5/menit/IP)
        $this->post('/kontak', $valid)->assertRedirect('/kontak');
        $this->post('/kontak', $valid)->assertRedirect('/kontak');
        $this->post('/kontak', $valid)->assertStatus(429);

        $message = ContactMessage::first();
        $this->actingAs($this->user('editor@mail.com'))->get(route('contact-message.show', $message))->assertOk()->assertSee('pindah cabang');
        $this->assertNotNull($message->fresh()->read_at);
        $this->actingAs($this->user('adminpc@mail.com'))->get(route('contact-message.index'))->assertForbidden();
    }

    public function test_home_stats_come_from_database_and_cache_is_flushed_on_change()
    {
        $html = $this->get('/')->assertOk()->getContent();
        $active = Member::where('status', 'aktif')->count();
        $this->assertStringContainsString(">{$active}</p>", $html);
        $this->assertTrue(Cache::has('site.stats'));

        Member::factory()->create();
        $this->assertFalse(Cache::has('site.stats'), 'cache statistik dibersihkan saat data anggota berubah');
        $this->assertStringContainsString('>' . ($active + 1) . '</p>', $this->get('/')->getContent());
    }

    public function test_slider_upload_limits_and_old_file_removed()
    {
        Storage::fake('public');
        $editor = $this->user('editor@mail.com');

        $this->actingAs($editor)->post(route('slider.store'), ['title' => 'Besar', 'image' => UploadedFile::fake()->image('big.jpg')->size(4000)])
            ->assertSessionHasErrors('image');

        $this->actingAs($editor)->post(route('slider.store'), ['title' => 'Raker 2026', 'image' => UploadedFile::fake()->image('a.jpg', 2400, 1000), 'is_active' => 1])
            ->assertSessionHasNoErrors();
        $slider = Slider::firstOrFail();
        $old = $slider->image;
        if (extension_loaded('gd')) {
            $this->assertSame(1920, getimagesize(Storage::disk('public')->path($old))[0], 'gambar lebar diperkecil');
        }

        $this->actingAs($editor)->patch(route('slider.update', $slider), ['title' => 'Raker 2026', 'image' => UploadedFile::fake()->image('b.jpg'), 'is_active' => 1]);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($slider->fresh()->image);
        $this->get('/')->assertSee('Raker 2026');

        $this->actingAs($editor)->delete(route('slider.destroy', $slider))->assertNoContent();
        Storage::disk('public')->assertMissing($slider->image);
    }

    public function test_gallery_multi_upload_and_drafts_hidden()
    {
        Storage::fake('public');
        $editor = $this->user('editor@mail.com');
        $this->actingAs($editor)->post(route('gallery.store'), ['title' => 'Seminar Pajak', 'published_at' => now()->toDateString()])->assertSessionHasNoErrors();
        $gallery = Gallery::firstOrFail();

        $this->actingAs($editor)->post(route('gallery.photos.store', $gallery), [
            'photos' => [UploadedFile::fake()->image('1.jpg'), UploadedFile::fake()->image('2.jpg'), UploadedFile::fake()->image('3.jpg')],
        ])->assertSessionHasNoErrors();
        $this->assertSame(3, $gallery->photos()->count());
        $this->get("/galeri/{$gallery->slug}")->assertOk()->assertSee('Seminar Pajak');
        $this->get('/')->assertSee($gallery->slug);

        $draft = Gallery::create(['title' => 'Draf Album']);
        $this->get("/galeri/{$draft->slug}")->assertNotFound();
        $this->get('/galeri')->assertDontSee('Draf Album');
    }

    public function test_documents_grouped_and_pdf_only()
    {
        Storage::fake('public');
        $editor = $this->user('editor@mail.com');

        $this->actingAs($editor)->post(route('document.store'), ['title' => 'AD/ART 2024', 'category' => 'AD/ART', 'file' => UploadedFile::fake()->create('x.docx', 10)])
            ->assertSessionHasErrors('file');
        $this->actingAs($editor)->post(route('document.store'), ['title' => 'AD/ART 2024', 'category' => 'AD/ART', 'published_at' => now()->toDateString(),
            'file' => UploadedFile::fake()->create('adart.pdf', 100, 'application/pdf')])->assertSessionHasNoErrors();
        Document::create(['title' => 'SK Draf', 'category' => 'SK', 'file' => 'documents/x.pdf']);

        $this->get('/peraturan-organisasi')->assertOk()->assertSeeInOrder(['AD/ART', 'AD/ART 2024'])->assertDontSee('SK Draf');
    }

    public function test_logout_returns_to_public_home()
    {
        $this->actingAs($this->user('member@mail.com'))->post('/logout')->assertRedirect('/');
        $this->get('/')->assertOk()->assertSee('Masuk');
    }
}
