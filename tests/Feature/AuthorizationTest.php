<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Comment;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Role & policy PRD 4.2–4.3, bug B2–B5 dan B13. */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function anotherAnggota(): User
    {
        return User::factory()->create()->assignRole('anggota');
    }

    private function blogPayload(array $override = []): array
    {
        return array_merge([
            'title' => 'Judul',
            'body' => 'Isi',
            'status' => 'published',
            'thumbnail' => UploadedFile::fake()->image('a.jpg'),
        ], $override);
    }

    public function test_roles_are_renamed_and_new_roles_exist()
    {
        $this->assertTrue($this->user('admin@mail.com')->hasRole('admin_pd'));
        $this->assertTrue($this->user('member@mail.com')->hasRole('anggota'));
        $this->assertTrue($this->user('general@mail.com')->hasRole('umum'));
        $this->assertTrue($this->user('editor@mail.com')->hasRole('editor'));
        $this->assertTrue($this->user('adminpc@mail.com')->hasRole('admin_pc'));
    }

    public function test_new_roles_can_log_in()
    {
        foreach (['editor@mail.com', 'adminpc@mail.com'] as $email) {
            $this->post('/login', ['email' => $email, 'password' => 'password'])->assertRedirect(route('home'));
            $this->post('/logout');
        }
    }

    public static function matrixSamples(): array
    {
        return [
            'editor tidak lihat data pengguna' => ['editor@mail.com', 'user-view', false],
            'editor kelola halaman' => ['editor@mail.com', 'page-create', true],
            'admin pc lihat anggota' => ['adminpc@mail.com', 'member-view', true],
            'admin pc tidak ubah anggota' => ['adminpc@mail.com', 'member-update', false],
            'admin pc kelola event' => ['adminpc@mail.com', 'event-create', true],
            'anggota tidak kelola event' => ['member@mail.com', 'event-create', false],
            'umum tidak menulis forum' => ['general@mail.com', 'thread-create', false],
            'umum baca jurnal' => ['general@mail.com', 'research-view', true],
            'admin pd pengaturan' => ['admin@mail.com', 'setting-update', true],
        ];
    }

    #[DataProvider('matrixSamples')]
    public function test_permission_matrix(string $email, string $permission, bool $expected)
    {
        $this->assertSame($expected, $this->user($email)->can($permission));
    }

    public function test_b13_anggota_cannot_see_other_users_pii()
    {
        $member = $this->user('member@mail.com');

        $this->actingAs($member)->get(route('member.index'))->assertForbidden();
        $this->actingAs($member)->get(route('general.index'))->assertForbidden();
        $this->actingAs($member)->getJson(route('member.datatable'), ['X-Requested-With' => 'XMLHttpRequest'])->assertForbidden();
        $this->actingAs($member)->get(route('member.show', $this->user('admin@mail.com')))->assertForbidden();
    }

    public function test_b2_anggota_cannot_edit_or_delete_others_blog()
    {
        $other = $this->anotherAnggota();
        $blog = Blog::factory()->create(['user_id' => $other->id]);
        $member = $this->user('member@mail.com');

        $this->actingAs($member)->get(route('blog.edit', $blog))->assertForbidden();
        $this->actingAs($member)->patch(route('blog.update', $blog), $this->blogPayload())->assertForbidden();
        $this->actingAs($member)->delete(route('blog.destroy', $blog))->assertForbidden();
        $this->assertModelExists($blog);
    }

    public function test_b2_editor_moderates_blog_of_others()
    {
        $blog = Blog::factory()->create(['user_id' => $this->anotherAnggota()->id]);

        $this->actingAs($this->user('editor@mail.com'))->delete(route('blog.destroy', $blog))->assertNoContent();
        $this->assertSoftDeleted($blog); // M3: blog soft delete
    }

    public function test_b2_thread_update_by_other_is_forbidden_and_owner_keeps_user_id()
    {
        $owner = $this->user('member@mail.com');
        $thread = Thread::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($this->anotherAnggota())
            ->patch(route('thread.update', $thread), ['title' => 'x', 'body' => 'y'])
            ->assertForbidden();

        // moderator mengedit: user_id tetap milik penulis
        $this->actingAs($this->user('editor@mail.com'))
            ->patch(route('thread.update', $thread), ['title' => 'Diedit', 'body' => 'y'])
            ->assertSessionHasNoErrors();
        $this->assertSame($owner->id, $thread->fresh()->user_id);
        $this->assertSame('Diedit', $thread->fresh()->title);
    }

    public function test_b3_comment_of_other_cannot_be_edited_or_deleted()
    {
        $thread = Thread::factory()->create(['user_id' => $this->user('member@mail.com')->id]);
        $comment = Comment::factory()->create(['thread_id' => $thread->id, 'user_id' => $this->anotherAnggota()->id]);
        $member = $this->user('member@mail.com');

        $this->actingAs($member)->get(route('comment.edit', $comment))->assertForbidden();
        $this->actingAs($member)->patch(route('comment.update', $comment), ['body' => 'hack'])->assertForbidden();
        $this->actingAs($member)->delete(route('comment.destroy', $comment))->assertForbidden();
        $this->actingAs($this->user('general@mail.com'))->delete(route('comment.destroy', $comment))->assertForbidden();
        $this->assertModelExists($comment);
    }

    public function test_b5_anggota_cannot_publish_blog_directly()
    {
        $member = $this->user('member@mail.com');

        $this->actingAs($member)->post(route('blog.store'), $this->blogPayload())->assertSessionHasNoErrors();
        $blog = Blog::where('user_id', $member->id)->latest('id')->firstOrFail();
        $this->assertSame('review', $blog->status);

        $this->actingAs($member)->patch(route('blog.update', $blog), $this->blogPayload(['thumbnail' => null]))->assertSessionHasNoErrors();
        $this->assertSame('review', $blog->fresh()->status);
    }

    public function test_b5_editor_can_publish_blog()
    {
        $this->actingAs($this->user('editor@mail.com'))->post(route('blog.store'), $this->blogPayload())->assertSessionHasNoErrors();

        $this->assertSame('published', Blog::latest('id')->first()->status);
    }

    public function test_b5_anggota_cannot_hide_or_change_thread_status()
    {
        $member = $this->user('member@mail.com');
        $this->actingAs($member)->post(route('thread.store'), ['title' => 'T', 'body' => 'B', 'status' => 'hidden']);
        $thread = Thread::where('user_id', $member->id)->latest('id')->firstOrFail();

        $this->assertSame('published', $thread->status);
    }

    public function test_b4_admin_pd_cannot_escalate_to_super_admin_or_admin_pd()
    {
        $admin = $this->user('admin@mail.com');
        $member = $this->user('member@mail.com');
        $payload = [
            'name' => $member->name, 'gender' => 'L', 'birthplace' => 'Bandung', 'date_of_birth' => '1990-01-01',
            'address' => 'Bandung', 'email' => $member->email, 'phone_number' => '0812',
            'division_id' => \App\Models\Division::first()->id, 'position_id' => \App\Models\Position::first()->id,
        ];

        foreach (['super_administrator', 'admin_pd'] as $role) {
            $this->actingAs($admin)->patch(route('member.update', $member), $payload + ['role' => $role])->assertSessionHasErrors('role');
            $this->assertFalse($member->fresh()->hasRole($role));
        }

        $this->actingAs($admin)->patch(route('member.update', $member), $payload + ['role' => 'editor'])->assertSessionHasNoErrors();
        $this->assertTrue($member->fresh()->hasRole('editor'));
    }

    public function test_b4_admin_pd_cannot_touch_super_admin_or_other_admin_pd()
    {
        $admin = $this->user('admin@mail.com');
        $super = $this->user('superadmin@mail.com');
        $otherAdmin = User::factory()->create()->assignRole('admin_pd');

        foreach ([$super, $otherAdmin] as $target) {
            $this->actingAs($admin)->get(route('member.edit', $target))->assertForbidden();
            $this->actingAs($admin)->patch(route('member.update', $target), ['role' => 'anggota'])->assertForbidden();
            $this->actingAs($admin)->delete(route('member.destroy', $target))->assertForbidden();
            // akun non-umum tidak bisa dibuka lewat halaman Umum
            $this->actingAs($admin)->get(route('general.edit', $target))->assertNotFound();
        }
        $this->assertTrue($super->fresh()->hasRole('super_administrator'));
        $this->actingAs($admin)->get(route('administrator.index'))->assertForbidden();
    }

    public function test_b4_general_assign_only_promotes_umum_accounts_to_allowed_roles()
    {
        $admin = $this->user('admin@mail.com');
        $umum = $this->user('general@mail.com');
        $division = \App\Models\Division::first();
        $position = \App\Models\Position::first();
        $base = ['division_id' => $division->id, 'position_id' => $position->id];

        $this->actingAs($admin)->patch(route('member.general-assign'), $base + ['user_id' => $umum->id, 'role' => 'super_administrator'])->assertSessionHasErrors('role');
        $this->actingAs($admin)->patch(route('member.general-assign'), $base + ['user_id' => $this->user('member@mail.com')->id, 'role' => 'anggota'])->assertNotFound();
        $this->actingAs($admin)->patch(route('member.general-assign'), $base + ['user_id' => $umum->id, 'role' => 'anggota'])->assertSessionHasNoErrors();

        $this->assertTrue($umum->fresh()->hasRole('anggota'));
    }
}
