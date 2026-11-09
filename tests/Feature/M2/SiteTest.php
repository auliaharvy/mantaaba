<?php

namespace Tests\Feature\M2;

use App\Models\ContactMessage;
use App\Models\Document;
use App\Models\Gallery;
use App\Models\Page;
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

/** PRD 8 & 9.1: situs publik + CMS (desain blok menyusul di M1) */
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
        $gallery = Gallery::create(['title' => 'Panen Perdana', 'published_at' => now()]);
        $uris = ['/', '/profil', '/galeri', "/galeri/{$gallery->slug}", '/dokumen', '/kontak', '/kebijakan-privasi',
            '/agenda', '/agenda?tab=selesai', '/agenda/' . \App\Models\Event::value('slug'), '/berita', '/berita/' . \App\Models\Blog::published()->value('slug')];

        foreach ($uris as $uri) {
            $html = $this->get($uri)->assertOk()->assertSee('<meta property="og:title"', false)->getContent();
            foreach (['akp2i', 'konsultan pajak', 'pengurus daerah', 'pusat pelatihan'] as $old) {
                $this->assertStringNotContainsStringIgnoringCase($old, $html, "{$uri} masih memuat '{$old}'");
            }
        }
        $this->get('/halaman/profil')->assertRedirect(route('site.profile'));
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee(route('site.gallery.show', $gallery->slug), false);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: ' . route('site.sitemap'), false);
        $this->get('/galeri/tidak-ada')->assertNotFound();
        foreach (['/pengurus', '/pengurus-cabang', '/keanggotaan', '/forum', '/jurnal', '/syarat-ketentuan'] as $removed) {
            $this->get($removed)->assertNotFound();
        }
    }

    public function test_pengurus_changes_profile_page_and_it_shows_immediately()
    {
        $page = Page::where('slug', 'profil')->firstOrFail();
        $this->get('/profil'); // isi cache

        $this->actingAs($this->user('pengurus@mail.com'))->patch(route('page.update', $page), [
            'title' => 'Profil', 'body' => '<p>Asrama, kebun, kolam, dan dapur dikelola siswa.</p><script>alert(1)</script>', 'is_published' => 1,
        ])->assertSessionHasNoErrors();

        $this->get('/profil')->assertOk()->assertSee('Asrama, kebun, kolam, dan dapur dikelola siswa.')->assertDontSee('alert(1)', false);
        $this->assertSame($this->user('pengurus@mail.com')->id, $page->fresh()->updated_by);
        $this->actingAs($this->user('anggota@mail.com'))->get(route('page.edit', $page))->assertForbidden();
    }

    public function test_fixed_pages_cannot_be_deleted()
    {
        $page = Page::first();
        $this->actingAs($this->user('superadmin@mail.com'))->delete("/dashboard/page/{$page->slug}")->assertStatus(405);
        $this->assertSame(3, Page::count());
    }

    public function test_setting_form_updates_site_identity()
    {
        Storage::fake('public');

        $this->actingAs($this->user('pengurus@mail.com'))->patch(route('setting.update'), [
            'site_name' => 'Rumah Mantaaba Uji', 'email' => 'info@contoh.id', 'logo' => UploadedFile::fake()->image('logo.png', 400, 400),
        ])->assertSessionHasNoErrors();

        $setting = Setting::current();
        Storage::disk('public')->assertExists($setting->logo);
        $this->get('/')->assertSee('Rumah Mantaaba Uji');
        $this->actingAs($this->user('anggota@mail.com'))->get(route('setting.edit'))->assertForbidden();
    }

    public function test_open_graph_preview_uses_page_title_and_image()
    {
        Storage::fake('public');
        $page = Page::where('slug', 'profil')->first();
        $this->actingAs($this->user('pengurus@mail.com'))->patch(route('page.update', $page), [
            'title' => 'Profil', 'meta_title' => 'Profil Rumah Mantaaba Sigaling', 'meta_description' => 'Sejarah dan visi misi.',
            'cover' => UploadedFile::fake()->image('cover.jpg', 1200, 630), 'is_published' => 1,
        ])->assertSessionHasNoErrors();

        $html = $this->get('/profil')->assertOk()->getContent();
        $this->assertStringContainsString('<meta property="og:title" content="Profil Rumah Mantaaba Sigaling">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="Sejarah dan visi misi.">', $html);
        $this->assertMatchesRegularExpression('#<meta property="og:image" content="[^"]+/storage/pages/[^"]+\.jpg">#', $html);
        $this->assertStringContainsString('<link rel="canonical" href="' . route('site.profile') . '">', $html);
    }

    public function test_contact_form_honeypot_notification_and_throttle()
    {
        Notification::fake();
        $valid = ['name' => 'Budi', 'email' => 'budi@contoh.id', 'subject' => 'Pendaftaran', 'message' => 'Kapan angkatan berikutnya dibuka?'];

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
        $this->actingAs($this->user('pengurus@mail.com'))->get(route('contact-message.show', $message))->assertOk()->assertSee('angkatan berikutnya');
        $this->assertNotNull($message->fresh()->read_at);
        $this->actingAs($this->user('anggota@mail.com'))->get(route('contact-message.index'))->assertForbidden();
    }

    public function test_home_stats_come_from_database_and_cache_is_flushed_on_change()
    {
        $html = $this->get('/')->assertOk()->getContent();
        $news = \App\Models\Blog::published()->count();
        $this->assertStringContainsString(">{$news}</p>", $html);
        $this->assertTrue(Cache::has('site.stats'));

        \App\Models\Blog::factory()->create(['user_id' => $this->user('pengurus@mail.com')->id, 'published_at' => now()->subMinute()]);
        $this->assertFalse(Cache::has('site.stats'), 'cache statistik dibersihkan saat berita berubah');
        $this->assertStringContainsString('>' . ($news + 1) . '</p>', $this->get('/')->getContent());
    }

    public function test_slider_upload_limits_and_old_file_removed()
    {
        Storage::fake('public');
        $editor = $this->user('pengurus@mail.com');

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
        $editor = $this->user('pengurus@mail.com');
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
        $editor = $this->user('pengurus@mail.com');

        $this->actingAs($editor)->post(route('document.store'), ['title' => 'Laporan 2026', 'category' => 'Laporan Transparansi', 'file' => UploadedFile::fake()->create('x.docx', 10)])
            ->assertSessionHasErrors('file');
        $this->actingAs($editor)->post(route('document.store'), ['title' => 'Laporan 2026', 'category' => 'Bukan Kategori', 'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('category');
        $this->actingAs($editor)->post(route('document.store'), ['title' => 'Laporan 2026', 'category' => 'Laporan Transparansi', 'published_at' => now()->toDateString(),
            'file' => UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf')])->assertSessionHasNoErrors();
        Document::create(['title' => 'Pedoman Draf', 'category' => 'Pedoman & Tata Tertib', 'file' => 'documents/x.pdf']);

        $this->get('/dokumen')->assertOk()->assertSeeInOrder(['Laporan Transparansi', 'Laporan 2026'])->assertDontSee('Pedoman Draf');
    }

    public function test_logout_returns_to_public_home()
    {
        $this->actingAs($this->user('anggota@mail.com'))->post('/logout')->assertRedirect('/');
        $this->get('/')->assertOk()->assertSee('Masuk');
    }
}
