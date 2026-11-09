<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Jaring pengaman upgrade: setiap halaman GET dashboard tanpa parameter
 * tidak boleh error 5xx untuk role mana pun.
 */
class DashboardSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function dashboardUris(): array
    {
        return collect(Route::getRoutes())
            ->filter(fn ($r) => in_array('GET', $r->methods())
                && Str::startsWith($r->uri(), 'dashboard')
                && ! Str::contains($r->uri(), '{'))
            ->map(fn ($r) => '/' . $r->uri())
            ->values()
            ->all();
    }

    public static function roleEmails(): array
    {
        return [
            'super admin' => ['superadmin@mail.com'],
            'admin pd' => ['admin@mail.com'],
            'editor' => ['editor@mail.com'],
            'admin pc' => ['adminpc@mail.com'],
            'anggota' => ['member@mail.com'],
            'umum' => ['general@mail.com'],
        ];
    }

    #[DataProvider('roleEmails')]
    public function test_dashboard_pages_do_not_error(string $email)
    {
        $user = User::where('email', $email)->firstOrFail();
        $uris = $this->dashboardUris();
        $this->assertNotEmpty($uris);

        foreach ($uris as $uri) {
            $status = $this->actingAs($user)->get($uri, ['X-Requested-With' => 'XMLHttpRequest'])->getStatusCode();
            $this->assertLessThan(500, $status, "GET {$uri} sebagai {$email} -> {$status}");
        }
    }

    /** Halaman show/edit dengan satu parameter model juga tidak boleh 5xx. */
    #[DataProvider('roleEmails')]
    public function test_dashboard_model_pages_do_not_error(string $email)
    {
        $models = [
            'blog' => \App\Models\Blog::first(), 'thread' => \App\Models\Thread::first(),
            'comment' => \App\Models\Comment::first(), 'research' => \App\Models\Research::first(),
            'event' => \App\Models\Event::first(), 'participant' => \App\Models\Participant::first(),
            'division' => \App\Models\Division::first(), 'position' => \App\Models\Position::first(),
            'setting' => \App\Models\Setting::first(),
            'member' => User::where('email', 'member@mail.com')->first(),
            'general' => User::where('email', 'general@mail.com')->first(),
            'administrator' => User::where('email', 'admin@mail.com')->first(),
            'organization' => \App\Models\Organization::pc()->first(),
            'membership' => \App\Models\Member::first(),
            'board' => \App\Models\BoardPeriod::first(),
            'due' => \App\Models\MemberDue::first(),
        ];
        $user = User::where('email', $email)->firstOrFail();

        foreach (Route::getRoutes() as $route) {
            preg_match_all('/\{(\w+)\}/', $route->uri(), $m);
            if (! in_array('GET', $route->methods()) || ! Str::startsWith($route->uri(), 'dashboard')
                || count($m[1]) !== 1 || ! isset($models[$m[1][0]])) {
                continue;
            }
            $uri = '/' . str_replace('{' . $m[1][0] . '}', $models[$m[1][0]]->getKey(), $route->uri());
            $status = $this->actingAs($user)->get($uri)->getStatusCode();
            $this->assertLessThan(500, $status, "GET {$uri} sebagai {$email} -> {$status}");
        }
    }

    public function test_guest_is_redirected_to_login()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
