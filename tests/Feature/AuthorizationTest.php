<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Blog;
use App\Models\User;
use App\Notifications\AccountActivation;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Role, hak akses per pengguna, dan layar Pengguna (PRD 5.2–5.4, 9.18; AC M0). */
class AuthorizationTest extends TestCase
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

    private function pengurusWith(array $permissions): User
    {
        return User::factory()->create()->assignRole('pengurus')->givePermissionTo($permissions);
    }

    private function userPayload(array $override = []): array
    {
        return array_merge(['name' => 'Baru', 'email' => 'baru@mail.com', 'phone_number' => '081234', 'role' => 'pengurus', 'is_active' => '1'], $override);
    }

    public function test_only_three_roles_exist_and_seeded_accounts_log_in()
    {
        $this->assertEqualsCanonicalizing(['super_admin', 'pengurus', 'anggota'], Role::pluck('name')->all());

        foreach (['superadmin@mail.com', 'pengurus@mail.com', 'anggota@mail.com'] as $email) {
            $this->post('/login', ['email' => $email, 'password' => 'password'])->assertRedirect('/dashboard');
            $this->post('/logout');
        }
    }

    public function test_roles_carry_no_permissions_and_pengurus_gets_default_package()
    {
        $this->assertTrue(Role::all()->every(fn ($role) => $role->permissions->isEmpty()));
        $pengurus = $this->user('pengurus@mail.com');

        $this->assertEqualsCanonicalizing(Permissions::package('pengurus_inti'), $pengurus->getDirectPermissions()->pluck('name')->all());
        $this->assertFalse($pengurus->can('journal-approve'));
        $this->assertFalse($pengurus->can('user-view'));
    }

    public function test_registration_and_api_are_gone()
    {
        $this->get('/register')->assertNotFound();
        $this->get('/api/user')->assertNotFound();
    }

    public function test_pengurus_without_module_permission_gets_403()
    {
        $pengurus = $this->user('pengurus@mail.com');
        $this->actingAs($pengurus)->get(route('page.index'))->assertOk();
        $this->actingAs($pengurus)->get(route('user.index'))->assertForbidden();

        $pengurus->revokePermissionTo('cms-view');
        $this->actingAs($pengurus->fresh())->get(route('page.index'))->assertForbidden();
        $this->actingAs($this->user('anggota@mail.com'))->get(route('blog.index'))->assertForbidden();
    }

    public function test_sidebar_only_shows_permitted_modules()
    {
        $this->actingAs($this->user('anggota@mail.com'))->get('/dashboard')->assertOk()
            ->assertSee('Agenda Saya')->assertDontSee('Setting Situs')->assertDontSee('Pengguna &amp; Hak Akses', false);
        $this->actingAs($this->user('pengurus@mail.com'))->get('/dashboard')->assertOk()
            ->assertSee('Setting Situs')->assertDontSee('Pengguna &amp; Hak Akses', false);
        $this->actingAs($this->user('superadmin@mail.com'))->get('/dashboard')->assertOk()
            ->assertSee('Pengguna &amp; Hak Akses', false);
    }

    public function test_non_super_admin_cannot_escalate_or_touch_super_admin()
    {
        $manager = $this->pengurusWith(['user-view', 'user-create', 'user-update']);
        $target = $this->user('anggota@mail.com');

        $this->actingAs($manager)->patch(route('user.update', $target), $this->userPayload(['email' => $target->email, 'role' => 'super_admin']))
            ->assertSessionHasErrors('role');
        $this->assertFalse($target->fresh()->hasRole('super_admin'));

        $this->actingAs($manager)->get(route('user.edit', $this->user('superadmin@mail.com')))->assertForbidden();
        $this->actingAs($manager)->get(route('user.permissions', $target))->assertForbidden();
        $this->actingAs($manager)->put(route('user.permissions.update', $target), ['permissions' => ['user-delete']])->assertForbidden();
    }

    public function test_super_admin_limits()
    {
        $admin = $this->user('superadmin@mail.com');

        // satu-satunya SA tidak bisa menurunkan / menonaktifkan dirinya
        $this->actingAs($admin)->patch(route('user.update', $admin), $this->userPayload(['email' => $admin->email, 'role' => 'pengurus']))->assertSessionHasErrors('role');
        $this->actingAs($admin)->patch(route('user.update', $admin), $this->userPayload(['email' => $admin->email, 'role' => 'super_admin', 'is_active' => '0']))->assertSessionHasErrors('role');
        $this->assertTrue($admin->fresh()->isSuperAdmin() && $admin->fresh()->is_active);

        // maksimal 3 SA
        User::factory()->count(2)->create()->each->assignRole('super_admin');
        $this->actingAs($admin)->post(route('user.store'), $this->userPayload(['role' => 'super_admin']))->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'baru@mail.com']);
    }

    public function test_super_admin_creates_pengurus_and_manages_permissions_with_audit_log()
    {
        $admin = $this->user('superadmin@mail.com');
        $this->actingAs($admin)->post(route('user.store'), $this->userPayload())->assertSessionHasNoErrors();
        $user = $this->user('baru@mail.com');
        $this->assertTrue($user->hasRole('pengurus'));
        $this->assertTrue($user->can('cms-view'));

        $this->actingAs($admin)->get(route('user.permissions', $user))->assertOk()->assertSee('Bendahara');
        $this->actingAs($admin)->put(route('user.permissions.update', $user), ['permissions' => Permissions::package('bendahara')])->assertSessionHasNoErrors();

        $user = $user->fresh();
        $this->assertTrue($user->can('journal-approve'));
        $this->assertFalse($user->can('cms-view'));
        $this->assertTrue(ActivityLog::where('action', 'permissions_changed')->where('subject_id', $user->id)->exists());
        $this->actingAs($admin)->put(route('user.permissions.update', $user), ['permissions' => ['bukan-permission']])->assertSessionHasErrors('permissions.0');
    }

    public function test_password_link_sends_email_and_whatsapp_link_with_same_token()
    {
        Notification::fake();
        $target = $this->user('anggota@mail.com');
        $target->update(['phone_number' => '0812-3456']);

        $response = $this->actingAs($this->user('superadmin@mail.com'))->post(route('user.password-link', $target));

        $response->assertSessionHas('wa_link', fn ($link) => str_starts_with($link, 'https://wa.me/628123456?text='));
        Notification::assertSentTo($target, AccountActivation::class, fn ($n) => str_contains(urldecode(session('wa_link')), $n->url));
    }

    public function test_inactive_account_cannot_log_in_and_active_session_is_ended()
    {
        $member = $this->user('anggota@mail.com');
        $member->update(['is_active' => false]);

        $this->post('/login', ['email' => $member->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertStringNotContainsString('AKP2I', session('errors')->first('email'));

        $this->actingAs($member)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_news_ownership_and_publish_permission()
    {
        $writer = $this->pengurusWith(['news-view', 'news-create', 'news-update', 'news-delete']);
        $other = Blog::factory()->create(['user_id' => $this->user('superadmin@mail.com')->id, 'status' => 'draft']);

        $this->actingAs($writer)->get(route('blog.edit', $other))->assertForbidden();
        $this->actingAs($writer)->delete(route('blog.destroy', $other))->assertForbidden();

        $this->actingAs($writer)->post(route('blog.store'), ['title' => 'Tulisan Baru', 'body' => 'Isi', 'status' => 'published']);
        $this->assertNotSame('published', Blog::where('title', 'Tulisan Baru')->value('status'));

        $editor = $this->pengurusWith(['news-view', 'news-create', 'news-update', 'news-publish']);
        $this->actingAs($editor)->get(route('blog.edit', $other))->assertOk();
    }
}
