<?php

namespace Tests\Feature\M1;

use App\Models\Blog;
use App\Models\BusinessUnit;
use App\Models\Document;
use App\Models\NavMenu;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Support\Blocks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** CMS M1 (PRD 9.1): halaman blok, menu, setting, unit bisnis, dokumen, cache (AC M1). */
class CmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function pengurus(): User
    {
        return $this->user('pengurus@mail.com');
    }

    /** Payload Setting minimal yang valid. */
    public static function settingPayload(array $override = []): array
    {
        return array_merge([
            'site_name' => 'Rumah Mantaaba Sigaling', 'foundation_name' => 'Yayasan Mantaaba',
            'color_primary' => '#5B3A1F', 'color_secondary' => '#C9A04A', 'color_accent' => '#8A2D1E', 'color_surface' => '#F7F1E6',
            'assessment_weights' => Setting::DEFAULT_WEIGHTS, 'default_pass_score' => 70, 'min_character_score' => 70,
        ], $override);
    }

    private function pagePayload(array $blocks, array $override = []): array
    {
        return array_merge(['title' => 'Kemitraan', 'template' => 'blocks', 'is_published' => 1, 'blocks_present' => 1, 'blocks' => $blocks], $override);
    }

    public function test_pengurus_builds_block_page_adds_it_to_menu_and_it_shows_without_deploy()
    {
        $this->actingAs($this->pengurus())->post(route('page.store'), ['title' => 'Kemitraan', 'template' => 'blocks'])->assertRedirect(route('page.edit', 'kemitraan'));
        $page = Page::where('slug', 'kemitraan')->firstOrFail();
        $this->assertFalse($page->is_published, 'halaman baru dimulai sebagai draf');
        $this->get('/kemitraan')->assertNotFound();

        $this->actingAs($this->pengurus())->patch(route('page.update', $page), $this->pagePayload([
            ['type' => 'hero', 'data' => ['title' => 'Bermitra dengan Mantaaba', 'overlay' => 40, 'button1_text' => 'Hubungi', 'button1_url' => '/kontak']],
            ['type' => 'cards', 'data' => ['title' => 'Bentuk kemitraan', 'columns' => '3', 'items' => [['icon' => 'heart', 'title' => 'Donasi bahan pangan'], ['title' => 'Magang siswa']]]],
            ['type' => 'text', 'hidden' => 1, 'data' => ['body' => '<p>Blok tersembunyi</p>']],
        ]))->assertSessionHasNoErrors();

        $this->actingAs($this->pengurus())->post(route('menu.store'), ['location' => 'header', 'label' => 'Kemitraan', 'type' => 'page', 'page_id' => $page->id, 'is_active' => 1])
            ->assertRedirect(route('menu.index'))->assertSessionHasNoErrors();

        $this->get('/kemitraan')->assertOk()->assertSeeInOrder(['Bermitra dengan Mantaaba', 'Donasi bahan pangan', 'Magang siswa'])->assertDontSee('Blok tersembunyi');
        $this->get('/')->assertSee('href="' . url('/kemitraan') . '"', false)->assertSee('Kemitraan');
    }

    public function test_editor_operations_add_duplicate_remove_and_items()
    {
        $page = Page::where('slug', 'program')->firstOrFail();
        $count = count($page->blocks);
        $send = fn (array $extra) => $this->actingAs($this->pengurus())->patch(route('page.update', $page), $this->pagePayload($page->fresh()->blocks, ['title' => 'Program Pembinaan'] + $extra));

        $send(['_op' => 'add', 'new_block' => 'faq'])->assertSessionHasNoErrors()->assertRedirect(route('page.edit', $page) . '#blok-' . $count);
        $this->assertSame('faq', $page->fresh()->blocks[$count]['type']);

        // FAQ baru punya 1 item kosong yang wajib diisi sebelum disimpan lagi
        $send(['_op' => 'duplicate:0'])->assertSessionHasErrors("blocks.{$count}.data.items.0.question");
        $blocks = $page->fresh()->blocks;
        $blocks[$count]['data']['items'] = [['question' => 'Tanya?', 'answer' => 'Jawab.']];
        $page->update(['blocks' => $blocks]);

        $send(['_op' => 'duplicate:0'])->assertSessionHasNoErrors();
        $blocks = $page->fresh()->blocks;
        $this->assertCount($count + 2, $blocks);
        $this->assertSame($blocks[0], $blocks[1]);

        $send(['_op' => 'remove:1'])->assertSessionHasNoErrors();
        $this->assertCount($count + 1, $page->fresh()->blocks);

        $send(['_op' => "add-item:{$count}"])->assertSessionHasNoErrors();
        $this->assertCount(2, $page->fresh()->blocks[$count]['data']['items']);
        // item & blok kosong yang baru ditambah bisa langsung dihapus tanpa diisi
        $send(['_op' => "remove-item:{$count}:1"])->assertSessionHasNoErrors();
        $this->assertEquals([['question' => 'Tanya?', 'answer' => 'Jawab.']], $page->fresh()->blocks[$count]['data']['items']);
        $send(['_op' => 'add', 'new_block' => 'hero'])->assertSessionHasNoErrors();
        $send(['_op' => 'remove:' . ($count + 1)])->assertSessionHasNoErrors();
        $this->assertCount($count + 1, $page->fresh()->blocks);
    }

    public function test_block_validation_requires_fields_and_whitelists_embed()
    {
        $page = Page::where('slug', 'program')->firstOrFail();
        $patch = fn (array $blocks) => $this->actingAs($this->pengurus())->patch(route('page.update', $page), $this->pagePayload($blocks, ['title' => 'Program']));

        $patch([['type' => 'embed', 'data' => ['url' => 'https://evil.example.com/embed/x']]])->assertSessionHasErrors('blocks.0.data.url');
        $patch([['type' => 'embed', 'data' => ['url' => 'https://www.google.com/maps/embed?pb=1"><script>']]])->assertSessionHasErrors('blocks.0.data.url');
        $patch([['type' => 'hero', 'data' => ['title' => '']]])->assertSessionHasErrors('blocks.0.data.title');
        $patch([['type' => 'cards', 'data' => ['items' => [['title' => '']]]]])->assertSessionHasErrors('blocks.0.data.items.0.title');
        $patch([['type' => 'cta', 'data' => ['title' => 'Daftar', 'button1_url' => 'javascript:alert(1)']]])->assertSessionHasErrors('blocks.0.data.button1_url');
        $patch([['type' => 'tidak-ada', 'data' => []]])->assertSessionHasErrors('blocks.0.type');

        $patch([['type' => 'embed', 'data' => ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'title' => 'Profil video']],
            ['type' => 'text', 'data' => ['body' => '<p>Isi</p><img src=x onerror="alert(1)">']]])->assertSessionHasNoErrors();
        $this->get('/program')->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false)->assertDontSee('onerror', false);
    }

    public function test_every_block_type_renders()
    {
        Storage::fake('public');
        $blocks = [];
        foreach (array_keys(Blocks::definitions()) as $type) {
            $block = Blocks::blank($type);
            foreach ([['title', 'Judul ' . $type], ['body', '<p>Isi</p>'], ['translation', 'Terjemahan'], ['url', 'https://youtu.be/dQw4w9WgXcQ']] as [$field, $value]) {
                if (array_key_exists($field, $block['data'])) {
                    $block['data'][$field] = $value;
                }
            }
            if (isset($block['data']['items'])) {
                $block['data']['items'] = [['title' => 'Item', 'label' => 'Angka', 'value' => '7', 'name' => 'Fulan', 'quote' => 'Kutipan', 'question' => 'Tanya?', 'answer' => 'Jawab']];
            }
            $blocks[] = $block;
        }
        $page = Page::where('slug', 'program')->firstOrFail();
        $this->actingAs($this->pengurus())->patch(route('page.update', $page), $this->pagePayload($blocks, ['title' => 'Program']))->assertSessionHasNoErrors();

        $this->get('/program')->assertOk()->assertSee('Judul cta')->assertSee('Terjemahan')->assertSee('Tanya?');
        $this->actingAs($this->pengurus())->get(route('page.edit', $page))->assertOk()->assertSee('Video YouTube');
    }

    public function test_block_image_upload()
    {
        Storage::fake('public');
        $page = Page::where('slug', 'program')->firstOrFail();
        $this->actingAs($this->pengurus())->patch(route('page.update', $page), $this->pagePayload([
            ['type' => 'hero', 'data' => ['title' => 'Hero', 'image_upload' => UploadedFile::fake()->image('hero.jpg', 1600, 900)]],
            ['type' => 'hero', 'data' => ['title' => 'Bukan gambar', 'image' => '../../.env']],
        ], ['title' => 'Program']))->assertSessionHasErrors('blocks.1.data.image');

        $this->actingAs($this->pengurus())->patch(route('page.update', $page), $this->pagePayload([
            ['type' => 'hero', 'data' => ['title' => 'Hero', 'image_upload' => UploadedFile::fake()->image('hero.jpg', 1600, 900)]],
        ], ['title' => 'Program']))->assertSessionHasNoErrors();
        $path = $page->fresh()->blocks[0]['data']['image'];
        Storage::disk('public')->assertExists($path);
        $this->get('/program')->assertSee(Storage::disk('public')->url($path), false);
    }

    public function test_color_setting_changes_site_css_variables()
    {
        $this->get('/')->assertSee('--color-primary:30 77 58', false);
        $this->actingAs($this->pengurus())->patch(route('setting.update'), self::settingPayload(['color_primary' => '#112233']))->assertSessionHasNoErrors();
        $this->get('/')->assertSee('--color-primary:17 34 51', false);

        $this->actingAs($this->pengurus())->patch(route('setting.update'), self::settingPayload(['color_accent' => 'red']))->assertSessionHasErrors('color_accent');
        $this->actingAs($this->pengurus())->patch(route('setting.update'), self::settingPayload(['assessment_weights' => ['knowledge' => 50, 'skill' => 25, 'character' => 30, 'social' => 25]]))
            ->assertSessionHasErrors('assessment_weights');
    }

    public function test_setting_bank_accounts_and_contact_show_on_donation_page()
    {
        $this->actingAs($this->pengurus())->patch(route('setting.update'), self::settingPayload([
            'bank_accounts' => [['bank' => 'BSI', 'number' => '7123456789', 'holder' => 'Yayasan Mantaaba'], ['bank' => '', 'number' => '', 'holder' => '']],
            'whatsapp' => '0812-1111-2222', 'priority_districts' => "Sigaling\nPurwareja Klampok",
        ]))->assertSessionHasNoErrors();

        $setting = Setting::current();
        $this->assertCount(1, $setting->bank_accounts);
        $this->assertSame(['Sigaling', 'Purwareja Klampok'], $setting->priority_districts);
        $this->get('/donasi')->assertOk()->assertSee('7123456789')->assertSee('https://wa.me/6281211112222', false);
    }

    public function test_system_page_and_page_in_menu_cannot_be_deleted()
    {
        $admin = $this->user('superadmin@mail.com');
        $this->actingAs($admin)->delete(route('page.destroy', 'home'))->assertForbidden();
        $this->actingAs($admin)->delete(route('page.destroy', 'kebijakan-privasi'))->assertForbidden();

        $this->actingAs($admin)->deleteJson(route('page.destroy', 'tentang'))->assertStatus(422)->assertJsonFragment(['message' => 'Halaman masih dipakai menu: Tentang Kami, Profil. Hapus atau ubah menu tersebut dulu.']);

        $free = Page::create(['slug' => 'uji', 'title' => 'Uji']);
        $this->actingAs($admin)->delete(route('page.destroy', $free))->assertNoContent();
        $this->assertModelMissing($free);
        $this->actingAs($this->user('anggota@mail.com'))->delete(route('page.destroy', 'program'))->assertForbidden();
    }

    public function test_page_slug_cannot_take_existing_routes()
    {
        foreach (['berita', 'dashboard', 'login', 'home', 'tentang'] as $slug) {
            $this->actingAs($this->pengurus())->post(route('page.store'), ['title' => 'X', 'slug' => $slug, 'template' => 'blocks'])->assertSessionHasErrors('slug');
        }
        $this->actingAs($this->pengurus())->post(route('page.store'), ['title' => 'Struktur Yayasan', 'template' => 'rich'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pages', ['slug' => 'struktur-yayasan', 'template' => 'rich']);
    }

    public function test_preview_needs_signature_and_permission()
    {
        $page = Page::create(['slug' => 'draf-uji', 'title' => 'Draf Uji', 'is_published' => false, 'blocks' => [['type' => 'text', 'data' => ['body' => '<p>Isi draf</p>']]]]);
        $signed = URL::temporarySignedRoute('page.preview', now()->addHour(), $page);

        $this->get('/draf-uji')->assertNotFound();
        $this->actingAs($this->pengurus())->get(route('page.preview', $page))->assertForbidden();
        $this->actingAs($this->pengurus())->get($signed)->assertOk()->assertSee('Isi draf')->assertSee('belum terbit')->assertSee('noindex', false);
        $this->actingAs($this->user('anggota@mail.com'))->get($signed)->assertForbidden();
    }

    public function test_menu_manager_levels_and_reorder()
    {
        $editor = $this->pengurus();
        $parent = NavMenu::where('location', 'header')->where('label', 'Tentang Kami')->whereNull('parent_id')->firstOrFail();

        $this->actingAs($editor)->post(route('menu.store'), ['location' => 'footer', 'parent_id' => $parent->id, 'label' => 'X', 'type' => 'url', 'url' => '/x'])
            ->assertSessionHasErrors('parent_id');
        $this->actingAs($editor)->post(route('menu.store'), ['location' => 'header', 'label' => 'X', 'type' => 'url', 'url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('url');
        $this->actingAs($editor)->post(route('menu.store'), ['location' => 'header', 'label' => 'X', 'type' => 'route', 'route_name' => 'login'])
            ->assertSessionHasErrors('route_name');

        $this->actingAs($editor)->post(route('menu.store'), ['location' => 'header', 'parent_id' => $parent->id, 'label' => 'Struktur Yayasan', 'type' => 'url', 'url' => '/struktur', 'is_active' => 1])
            ->assertSessionHasNoErrors();
        $child = NavMenu::where('label', 'Struktur Yayasan')->firstOrFail();
        // menu tingkat 2 tidak bisa menjadi induk; menu yang punya anak tidak bisa jadi anak
        $this->actingAs($editor)->post(route('menu.store'), ['location' => 'header', 'parent_id' => $child->id, 'label' => 'Cucu', 'type' => 'url', 'url' => '/c'])
            ->assertSessionHasErrors('parent_id');
        $other = NavMenu::where('label', 'Kontak')->where('location', 'header')->firstOrFail();
        $this->actingAs($editor)->patch(route('menu.update', $parent), ['location' => 'header', 'parent_id' => $other->id, 'label' => 'Tentang Kami', 'type' => 'page', 'page_id' => $parent->page_id])
            ->assertSessionHasErrors('parent_id');

        $this->get('/')->assertSee('Struktur Yayasan');

        $ids = NavMenu::where('location', 'footer')->orderBy('sort_order')->pluck('id')->reverse()->values()->all();
        $this->actingAs($editor)->post(route('menu.reorder'), ['order' => $ids])->assertRedirect(route('menu.index'));
        $this->assertSame($ids, NavMenu::where('location', 'footer')->orderBy('sort_order')->pluck('id')->all());
        $this->assertStringContainsString('Kontak</a></li>', explode('Tautan</h2>', $this->get('/')->getContent())[1]);

        $this->actingAs($this->user('anggota@mail.com'))->post(route('menu.reorder'), ['order' => $ids])->assertForbidden();
    }

    public function test_member_documents_need_login_and_files_are_private()
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->actingAs($this->pengurus())->post(route('document.store'), ['title' => 'Tata Tertib Asrama', 'category' => 'Pedoman & Tata Tertib', 'visibility' => 'anggota',
            'published_at' => now()->subDay()->toDateString(), 'file' => UploadedFile::fake()->create('tatib.pdf', 50, 'application/pdf')])->assertSessionHasNoErrors();
        $this->actingAs($this->pengurus())->post(route('document.store'), ['title' => 'Laporan Keuangan 2026', 'category' => 'Laporan Transparansi', 'visibility' => 'publik',
            'published_at' => now()->subDay()->toDateString(), 'file' => UploadedFile::fake()->create('lap.pdf', 50, 'application/pdf')])->assertSessionHasNoErrors();
        $member = Document::where('visibility', 'anggota')->firstOrFail();
        $public = Document::where('visibility', 'publik')->firstOrFail();

        Storage::disk('local')->assertExists($member->file);
        $this->assertEmpty(Storage::disk('public')->allFiles(), 'file dokumen tidak punya URL publik');

        auth()->logout();
        $this->get('/dokumen')->assertSee('Laporan Keuangan 2026')->assertDontSee('Tata Tertib Asrama');
        $this->get(route('site.documents.download', $member))->assertRedirect(route('login'));
        $this->get(route('site.documents.download', $public))->assertOk();

        $this->actingAs($this->user('anggota@mail.com'))->get('/dokumen')->assertSee('Tata Tertib Asrama');
        $this->actingAs($this->user('anggota@mail.com'))->get(route('site.documents.download', $member))->assertOk();

        $member->update(['published_at' => null]);
        $this->actingAs($this->user('anggota@mail.com'))->get(route('site.documents.download', $member))->assertNotFound();
        $this->actingAs($this->pengurus())->get(route('site.documents.download', $member))->assertOk();
    }

    public function test_site_cache_is_flushed_when_page_menu_setting_or_news_change()
    {
        $this->get('/tentang')->assertSee('Rumah Mantaaba Sigaling'); // isi cache
        $page = Page::where('slug', 'tentang')->first();
        $page->update(['blocks' => [['type' => 'text', 'data' => ['body' => '<p>Teks baru tentang</p>']]]]);
        $this->get('/tentang')->assertSee('Teks baru tentang');

        NavMenu::where('label', 'Berita')->first()->update(['label' => 'Kabar']);
        $this->get('/')->assertSee('>Kabar</a>', false);

        Setting::current()->update(['footer_text' => 'Footer baru']);
        $this->get('/')->assertSee('Footer baru');

        Blog::factory()->create(['title' => 'Berita Segar', 'user_id' => $this->pengurus()->id, 'published_at' => now()->subMinute()]);
        $this->get('/')->assertSee('Berita Segar');
    }

    public function test_news_is_draft_or_published_and_can_be_scheduled()
    {
        $editor = User::factory()->create()->assignRole('pengurus')->givePermissionTo(['news-view', 'news-create', 'news-update', 'news-publish']);
        $this->actingAs($editor)->post(route('blog.store'), ['title' => 'Terjadwal', 'body' => 'Isi', 'status' => 'published', 'published_at' => now()->addDay()->format('Y-m-d\TH:i')])
            ->assertSessionHas('success', fn ($m) => str_starts_with($m, 'Tulisan dijadwalkan'));
        $this->get('/berita')->assertDontSee('Terjadwal');
        $this->travel(2)->days();
        $this->get('/berita')->assertSee('Terjadwal');

        $this->actingAs($editor)->post(route('blog.store'), ['title' => 'Status aneh', 'body' => 'Isi', 'status' => 'review'])->assertSessionHasErrors('status');
    }

    public function test_business_units_public_pages()
    {
        $this->actingAs($this->pengurus())->post(route('business-unit.store'), ['name' => 'Mantaaba Katering', 'type' => 'unit_bisnis', 'field' => 'Katering', 'summary' => 'Katering sehat', 'is_public' => 1])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->pengurus())->post(route('business-unit.store'), ['name' => 'Unit Rahasia', 'type' => 'unit_bisnis'])->assertSessionHasNoErrors();

        $this->get('/ekosistem')->assertOk()->assertSee('Mantaaba Katering')->assertSee('Kebun')->assertDontSee('Unit Rahasia');
        $this->get('/ekosistem/mantaaba-katering')->assertOk()->assertSee('Katering sehat');
        $this->get('/ekosistem/unit-rahasia')->assertNotFound();
        $this->get('/')->assertSee('Mantaaba Katering'); // blok `units` di beranda
        $this->get('/sitemap.xml')->assertSee(route('site.units.show', 'mantaaba-katering'), false)->assertDontSee('unit-rahasia');
        $this->actingAs($this->user('anggota@mail.com'))->get(route('business-unit.index'))->assertForbidden();
    }

    public function test_sitemap_respects_show_in_sitemap()
    {
        Page::where('slug', 'program')->update(['show_in_sitemap' => false]);
        $this->get('/sitemap.xml')->assertOk()->assertSee(url('/tentang'), false)->assertDontSee(url('/program'), false)->assertSee(route('site.curriculum'), false);
    }

    public function test_auth_pages_use_site_style()
    {
        foreach (['/login', '/forgot-password'] as $uri) {
            $this->get($uri)->assertOk()->assertSee('--color-primary', false)->assertSee('noindex', false)->assertDontSee('plugins.bundle.css', false);
        }
    }
}
