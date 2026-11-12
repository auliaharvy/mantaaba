<?php

namespace Tests\Feature\M1;

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

/** PRD 8 & 9.1: situs publik + konten CMS (lihat juga CmsTest untuk blok, menu, dan cache). */
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
        $uris = ['/', '/tentang', '/program', '/kurikulum', '/ekosistem', '/ekosistem/' . \App\Models\BusinessUnit::value('slug'), '/galeri', "/galeri/{$gallery->slug}",
            '/dokumen', '/pendaftaran', '/donasi', '/kontak', '/kebijakan-privasi', '/login', '/forgot-password',
            '/agenda', '/agenda?tab=selesai', '/agenda/' . \App\Models\Event::value('slug'), '/berita', '/berita/' . \App\Models\Blog::published()->value('slug')];

        foreach ($uris as $uri) {
            $html = $this->get($uri)->assertOk()->assertSee('<meta property="og:title"', false)->getContent();
            foreach (['akp2i', 'konsultan pajak', 'pengurus daerah', 'pusat pelatihan'] as $old) {
                $this->assertStringNotContainsStringIgnoringCase($old, $html, "{$uri} masih memuat '{$old}'");
            }
        }
        $this->get('/home')->assertRedirect('/');
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee(route('site.gallery.show', $gallery->slug), false);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: ' . route('site.sitemap'), false);
        $this->get('/galeri/tidak-ada')->assertNotFound();
        foreach (['/pengurus', '/pengurus-cabang', '/keanggotaan', '/forum', '/jurnal', '/syarat-ketentuan', '/profil', '/halaman/profil'] as $removed) {
            $this->get($removed)->assertNotFound();
        }
    }

    public function test_pengurus_changes_rich_page_and_it_shows_immediately()
    {
        $page = Page::where('slug', 'kebijakan-privasi')->firstOrFail();
        $this->get('/kebijakan-privasi'); // isi cache

        $this->actingAs($this->user('pengurus@mail.com'))->patch(route('page.update', $page), [
            'title' => 'Kebijakan Privasi', 'template' => 'rich', 'body' => '<p>Data anak hanya untuk pembinaan.</p><script>alert(1)</script>',
        ])->assertSessionHasNoErrors();

        $this->get('/kebijakan-privasi')->assertOk()->assertSee('Data anak hanya untuk pembinaan.')->assertDontSee('alert(1)', false);
        $this->assertTrue($page->fresh()->is_published, 'halaman sistem selalu terbit');
        $this->assertSame($this->user('pengurus@mail.com')->id, $page->fresh()->updated_by);
        $this->actingAs($this->user('anggota@mail.com'))->get(route('page.edit', $page))->assertForbidden();
    }

    public function test_setting_form_updates_site_identity()
    {
        Storage::fake('public');

        $this->actingAs($this->user('pengurus@mail.com'))->patch(route('setting.update'), CmsTest::settingPayload([
            'site_name' => 'Rumah Mantaaba Uji', 'email' => 'info@contoh.id', 'logo' => UploadedFile::fake()->image('logo.png', 400, 400),
        ]))->assertSessionHasNoErrors();

        $setting = Setting::current();
        Storage::disk('public')->assertExists($setting->logo);
        $this->get('/')->assertSee('Rumah Mantaaba Uji');
        $this->actingAs($this->user('anggota@mail.com'))->get(route('setting.edit'))->assertForbidden();
    }

    public function test_open_graph_preview_uses_page_title_and_image()
    {
        Storage::fake('public');
        $page = Page::where('slug', 'tentang')->first();
        $this->actingAs($this->user('pengurus@mail.com'))->patch(route('page.update', $page), [
            'title' => 'Tentang Kami', 'template' => 'blocks', 'meta_title' => 'Profil Rumah Mantaaba Sigaling', 'meta_description' => 'Sejarah dan visi misi.',
            'cover' => UploadedFile::fake()->image('cover.jpg', 1200, 630), 'is_published' => 1,
        ])->assertSessionHasNoErrors();

        $html = $this->get('/tentang')->assertOk()->getContent();
        $this->assertStringContainsString('<meta property="og:title" content="Profil Rumah Mantaaba Sigaling">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="Sejarah dan visi misi.">', $html);
        $this->assertMatchesRegularExpression('#<meta property="og:image" content="[^"]+/storage/pages/[^"]+\.jpg">#', $html);
        $this->assertStringContainsString('<link rel="canonical" href="' . route('site.page', 'tentang') . '">', $html);
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

    public function test_home_latest_news_and_cache_is_flushed_on_change()
    {
        $this->get('/')->assertOk()->assertSee(\App\Models\Blog::published()->latest('published_at')->value('title'));

        \App\Models\Blog::factory()->create(['title' => 'Panen Kangkung Perdana', 'user_id' => $this->user('pengurus@mail.com')->id, 'published_at' => now()->subMinute()]);
        $this->get('/')->assertSee('Panen Kangkung Perdana');
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

        $this->actingAs($editor)->delete(route('slider.destroy', $slider))->assertNoContent();
        Storage::disk('public')->assertMissing($slider->image);
    }

    public function test_gallery_multi_upload_and_drafts_hidden()
    {
        Storage::fake('public');
        $editor = $this->user('pengurus@mail.com');
        $this->actingAs($editor)->post(route('gallery.store'), ['title' => 'Seminar Pajak', 'published_at' => now()->toDateString()])->assertSessionHasNoErrors();
        $gallery = Gallery::latest('id')->firstOrFail();

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
        Storage::fake('local');
        $editor = $this->user('pengurus@mail.com');

        $this->actingAs($editor)->post(route('document.store'), ['title' => 'Laporan 2026', 'category' => 'Laporan Transparansi', 'visibility' => 'publik', 'file' => UploadedFile::fake()->create('x.docx', 10)])
            ->assertSessionHasErrors('file');
        $this->actingAs($editor)->post(route('document.store'), ['title' => 'Laporan 2026', 'category' => 'Bukan Kategori', 'visibility' => 'publik', 'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('category');
        $this->actingAs($editor)->post(route('document.store'), ['title' => 'Laporan 2026', 'category' => 'Laporan Transparansi', 'visibility' => 'publik', 'published_at' => now()->toDateString(),
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
