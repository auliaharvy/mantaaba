<?php

namespace Tests\Feature\M3;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Member;
use App\Models\Report;
use App\Models\Research;
use App\Models\Thread;
use App\Models\User;
use App\Notifications\BlogAwaitingReview;
use App\Notifications\ForumReplyPosted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** PRD 8.6 (blog), 8.7 (forum), 8.8 (jurnal), 8.11 (tulisan saya). */
class ContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function otherAnggota(): User
    {
        return Member::factory()->create()->user;
    }

    private function blogPayload(array $override = []): array
    {
        return array_merge(['title' => 'Update PMK Terbaru', 'body' => '<p>Isi tulisan.</p>', 'category_id' => Category::first()->id, 'tags' => 'PPh 21, e-Bupot'], $override);
    }

    // ---------------- Blog (8.6)

    public function test_member_patch_with_published_status_stays_in_review_and_editor_is_notified()
    {
        Notification::fake();
        $member = $this->user('member@mail.com');

        $this->actingAs($member)->post(route('blog.store'), $this->blogPayload(['status' => 'published']))->assertSessionHasNoErrors();
        $blog = Blog::where('user_id', $member->id)->latest('id')->firstOrFail();
        $this->assertSame('review', $blog->status);
        $this->assertNull($blog->published_at);
        $this->assertSame($member->member->organization_id, $blog->organization_id);
        $this->assertEqualsCanonicalizing(['PPh 21', 'e-Bupot'], $blog->tags->pluck('name')->all());
        Notification::assertSentTo($this->user('editor@mail.com'), BlogAwaitingReview::class);

        $this->actingAs($member)->patch(route('blog.update', $blog), $this->blogPayload(['status' => 'published']))->assertSessionHasNoErrors();
        $this->assertSame('review', $blog->fresh()->status);

        $this->actingAs($member)->patch(route('blog.update', $blog), $this->blogPayload(['status' => 'draft']));
        $this->assertSame('draft', $blog->fresh()->status);
    }

    public function test_editor_publishes_and_author_can_no_longer_edit()
    {
        $member = $this->user('member@mail.com');
        $blog = Blog::factory()->create(['user_id' => $member->id, 'status' => 'review', 'published_at' => null]);

        $this->actingAs($this->user('editor@mail.com'))->patch(route('blog.update', $blog), $this->blogPayload(['status' => 'published']))->assertSessionHasNoErrors();
        $this->assertSame('published', $blog->fresh()->status);
        $this->assertNotNull($blog->fresh()->published_at);

        $this->actingAs($member)->get(route('blog.edit', $blog))->assertForbidden();
        $this->actingAs($member)->patch(route('blog.update', $blog), $this->blogPayload())->assertForbidden();
        $this->actingAs($member)->delete(route('blog.destroy', $blog))->assertForbidden();
    }

    public function test_member_cannot_edit_or_delete_blog_of_other_member()
    {
        $blog = Blog::factory()->create(['user_id' => $this->otherAnggota()->id, 'status' => 'draft', 'published_at' => null]);
        $member = $this->user('member@mail.com');

        $this->actingAs($member)->patch(route('blog.update', $blog), $this->blogPayload())->assertForbidden();
        $this->actingAs($member)->delete(route('blog.destroy', $blog))->assertForbidden();
        $this->actingAs($member)->get(route('blog.show', $blog))->assertForbidden();
    }

    public function test_slug_never_changes_when_title_is_edited()
    {
        $editor = $this->user('editor@mail.com');
        $this->actingAs($editor)->post(route('blog.store'), $this->blogPayload(['title' => 'Judul Awal']));
        $blog = Blog::latest('id')->firstOrFail();
        $this->assertSame('judul-awal', $blog->slug);

        $this->actingAs($editor)->patch(route('blog.update', $blog), $this->blogPayload(['title' => 'Judul Baru Sekali']));
        $this->assertSame('judul-awal', $blog->fresh()->slug);
        $this->get('/berita/judul-awal')->assertOk()->assertSee('Judul Baru Sekali');
    }

    public function test_public_news_pages_filters_views_and_share_preview()
    {
        Storage::fake('public');
        $editor = $this->user('editor@mail.com');
        $category = Category::first();
        $this->actingAs($editor)->post(route('blog.store'), $this->blogPayload([
            'title' => 'Sosialisasi Coretax', 'excerpt' => 'Ringkasan sosialisasi Coretax DJP.', 'category_id' => $category->id,
            'thumbnail' => UploadedFile::fake()->image('c.jpg', 1200, 750),
        ]));
        $blog = Blog::where('title', 'Sosialisasi Coretax')->firstOrFail();
        Blog::factory()->create(['title' => 'Draf Rahasia', 'status' => 'draft', 'published_at' => null]);
        $this->app['auth']->forgetGuards();

        $this->get('/berita')->assertOk()->assertSee('Sosialisasi Coretax')->assertDontSee('Draf Rahasia');
        $this->get("/berita/kategori/{$category->slug}")->assertOk()->assertSee('Sosialisasi Coretax');
        $this->get('/berita/tag/pph-21')->assertOk()->assertSee('Sosialisasi Coretax');
        $this->get('/berita?q=Coretax')->assertOk()->assertSee('Sosialisasi Coretax');
        $this->get('/berita/' . Blog::where('title', 'Draf Rahasia')->value('slug'))->assertNotFound();

        $html = $this->get("/berita/{$blog->slug}")->assertOk()->assertSee('wa.me/?text=', false)->getContent();
        $this->assertStringContainsString('<meta property="og:type" content="article">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="Sosialisasi Coretax">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="Ringkasan sosialisasi Coretax DJP.">', $html);
        $this->assertMatchesRegularExpression('#<meta property="og:image" content="http[^"]+/storage/blog/[^"]+\.jpg">#', $html);

        // views +1 per sesi
        $this->get("/berita/{$blog->slug}");
        $this->assertSame(1, $blog->fresh()->views);
        $this->get('/')->assertSee('Sosialisasi Coretax');
        $this->get('/sitemap.xml')->assertSee(route('site.news.show', $blog->slug), false);
    }

    // ---------------- Forum (8.7)

    public function test_editing_others_thread_forbidden_and_owner_edit_keeps_user_id()
    {
        $owner = $this->user('member@mail.com');
        $thread = Thread::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($this->otherAnggota())->patch(route('thread.update', $thread), ['title' => 'x', 'body' => 'y'])->assertForbidden();
        $this->actingAs($owner)->patch(route('thread.update', $thread), ['title' => 'Judul Diubah', 'body' => '<p>baru</p>'])->assertSessionHasNoErrors();
        $this->assertSame($owner->id, $thread->fresh()->user_id);
        $this->assertSame('Judul Diubah', $thread->fresh()->title);
    }

    public function test_locked_thread_rejects_comments_even_for_super_admin()
    {
        $thread = Thread::factory()->create(['user_id' => $this->user('member@mail.com')->id]);
        $this->actingAs($this->user('editor@mail.com'))->patch(route('thread.moderate', $thread), ['action' => 'lock'])->assertSessionHas('success');
        $this->assertTrue($thread->fresh()->is_locked);

        foreach (['member@mail.com', 'superadmin@mail.com'] as $email) {
            $this->actingAs($this->user($email))->post(route('site.forum.comment', $thread->slug), ['body' => 'Halo'])->assertForbidden();
        }
        $this->assertSame(0, $thread->comments()->count());
        $this->actingAs($this->user('member@mail.com'))->patch(route('thread.moderate', $thread), ['action' => 'unlock'])->assertForbidden();
    }

    public function test_reply_one_level_notification_and_opt_out()
    {
        Notification::fake();
        $owner = $this->user('member@mail.com');
        $thread = Thread::factory()->create(['user_id' => $owner->id]);
        $replier = $this->otherAnggota();

        $this->actingAs($replier)->post(route('site.forum.comment', $thread->slug), ['body' => 'Komentar pertama'])->assertRedirect();
        $root = Comment::latest('id')->first();
        Notification::assertSentTo($owner, ForumReplyPosted::class);

        // balasan atas balasan disimpan sebagai balasan komentar induk (satu level)
        $this->actingAs($owner)->post(route('site.forum.comment', $thread->slug), ['body' => 'Balasan', 'parent_id' => $root->id]);
        $reply = Comment::latest('id')->first();
        $this->actingAs($replier)->post(route('site.forum.comment', $thread->slug), ['body' => 'Balas lagi', 'parent_id' => $reply->id]);
        $this->assertSame($root->id, Comment::latest('id')->first()->parent_id);

        $this->actingAs($owner)->patch(route('profile.notifications'), [])->assertRedirect();
        $this->assertFalse($owner->fresh()->notify_forum_replies);
        Notification::fake();
        $this->actingAs($replier)->post(route('site.forum.comment', $thread->slug), ['body' => 'Tanpa notifikasi']);
        Notification::assertNothingSent();

        $this->get("/forum/{$thread->slug}")->assertOk()->assertSee('Komentar pertama')->assertSee('Balas lagi');
    }

    public function test_eleventh_comment_within_a_minute_is_rejected()
    {
        $member = $this->user('member@mail.com');
        RateLimiter::clear(sha1('comments' . $member->id));
        $thread = Thread::factory()->create();

        for ($i = 1; $i <= 10; $i++) {
            $this->actingAs($member)->post(route('site.forum.comment', $thread->slug), ['body' => "Komentar {$i}"])->assertRedirect();
        }
        $this->actingAs($member)->post(route('site.forum.comment', $thread->slug), ['body' => 'Ke-11'])->assertStatus(429);
        $this->assertSame(10, $thread->comments()->count());
    }

    public function test_umum_reads_publicly_but_cannot_write_q6_default()
    {
        $thread = Thread::factory()->create();
        $this->get('/forum')->assertOk()->assertSee($thread->title);
        $this->get("/forum/{$thread->slug}")->assertOk()->assertSee('Masuk');

        $this->actingAs($this->user('general@mail.com'))->post(route('site.forum.comment', $thread->slug), ['body' => 'Boleh?'])->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->post(route('site.forum.comment', $thread->slug), ['body' => 'Tamu'])->assertRedirect('/login');
    }

    public function test_hidden_thread_only_for_owner_and_moderator_and_reports_queue()
    {
        $owner = $this->user('member@mail.com');
        $thread = Thread::factory()->create(['user_id' => $owner->id]);
        $reporter = $this->otherAnggota();

        $this->actingAs($reporter)->post(route('site.forum.report'), ['type' => 'thread', 'id' => $thread->id, 'reason' => 'Promosi berlebihan'])->assertSessionHas('success');
        $report = Report::firstOrFail();
        $this->actingAs($this->user('editor@mail.com'))->get(route('report.index'))->assertOk();
        $this->actingAs($owner)->get(route('report.index'))->assertForbidden();

        $this->actingAs($this->user('editor@mail.com'))->patch(route('report.resolve', $report), ['take_down' => 1])->assertSessionHas('success');
        $this->assertSame('hidden', $thread->fresh()->status);
        $this->assertNotNull($report->fresh()->resolved_at);

        $this->app['auth']->forgetGuards();
        $this->get("/forum/{$thread->slug}")->assertNotFound();
        $this->get('/forum')->assertDontSee($thread->title);
        $this->actingAs($owner)->get("/forum/{$thread->slug}")->assertOk()->assertSee('disembunyikan moderator');
        $this->actingAs($reporter)->get("/forum/{$thread->slug}")->assertNotFound();
    }

    // ---------------- Jurnal (8.8)

    public function test_draft_journal_hidden_and_not_downloadable_and_downloads_counted()
    {
        Storage::fake('local');
        Storage::fake('public');
        $editor = $this->user('editor@mail.com');
        $pdf = fn () => UploadedFile::fake()->create('j.pdf', 50, 'application/pdf');
        $this->actingAs($editor)->post(route('research.store'), ['title' => 'Jurnal Terbit', 'date_of_publish' => '2025-03-01', 'status' => 'published', 'authors' => 'A, B', 'keywords' => 'pph, umkm', 'file' => $pdf()])->assertSessionHasNoErrors();
        $this->actingAs($editor)->post(route('research.store'), ['title' => 'Jurnal Draf', 'date_of_publish' => '2025-03-01', 'status' => 'draft', 'file' => $pdf()])->assertSessionHasNoErrors();
        $published = Research::where('title', 'Jurnal Terbit')->firstOrFail();
        $draft = Research::where('title', 'Jurnal Draf')->firstOrFail();
        Storage::disk('local')->assertExists($draft->file);
        Storage::disk('public')->assertMissing($draft->file);
        $this->app['auth']->forgetGuards();

        $this->get('/jurnal')->assertOk()->assertSee('Jurnal Terbit')->assertDontSee('Jurnal Draf');
        $this->get('/jurnal?q=umkm&tahun=2025')->assertSee('Jurnal Terbit');
        $this->get("/jurnal/{$draft->slug}")->assertNotFound();
        $this->get("/jurnal/{$draft->slug}/unduh")->assertNotFound();

        $this->get("/jurnal/{$published->slug}")->assertOk()->assertSee('A, B. (2025). Jurnal Terbit.');
        $this->get("/jurnal/{$published->slug}/unduh")->assertOk()->assertDownload('jurnal-terbit.pdf');
        $this->assertSame(1, $published->fresh()->downloads);
    }

    public function test_admin_tables_search_and_sort_every_column()
    {
        $tables = [
            'blog.datatable' => ['DT_RowIndex', 'blogs.title', 'category.name', 'user.name', 'blogs.status', 'blogs.updated_at'],
            'thread.datatable' => ['DT_RowIndex', 'threads.title', 'user.name', 'comments_count', 'threads.last_activity_at'],
            'research.datatable' => ['DT_RowIndex', 'title', 'authors', 'category', 'date_of_publish', 'downloads', 'status'],
            'report.datatable' => ['DT_RowIndex', 'reports.created_at', 'user.name', 'reason'],
        ];
        Report::create(['reportable_type' => Thread::class, 'reportable_id' => Thread::value('id'), 'user_id' => $this->user('member@mail.com')->id, 'reason' => 'uji laporan']);

        foreach ($tables as $route => $columns) {
            $cols = array_map(fn ($c) => ['data' => $c, 'name' => $c, 'searchable' => in_array($c, ['DT_RowIndex', 'comments_count']) ? 'false' : 'true', 'orderable' => 'true', 'search' => ['value' => '']], $columns);
            foreach (array_keys($columns) as $i) {
                $json = $this->actingAs($this->user('editor@mail.com'))->getJson(route($route, ['draw' => 1, 'start' => 0, 'length' => 5, 'columns' => $cols,
                    'search' => ['value' => 'a'], 'order' => [['column' => $i, 'dir' => 'desc']]]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json();
                $this->assertArrayNotHasKey('error', $json, "{$route} urut {$columns[$i]}: " . ($json['error'] ?? ''));
            }
        }
    }

    // ---------------- Profil (8.11)

    public function test_profile_lists_my_writings()
    {
        $member = $this->user('member@mail.com');
        Blog::factory()->create(['user_id' => $member->id, 'title' => 'Tulisanku Sendiri', 'status' => 'review']);
        Thread::factory()->create(['user_id' => $member->id, 'title' => 'Diskusiku Sendiri?']);

        $this->actingAs($member)->get(route('profile.index'))->assertOk()->assertSee('Tulisanku Sendiri')->assertSee('Diskusiku Sendiri?')->assertSee('Menunggu review');
    }
}
