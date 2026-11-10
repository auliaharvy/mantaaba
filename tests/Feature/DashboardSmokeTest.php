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
            'pengurus' => ['pengurus@mail.com'],
            'anggota' => ['anggota@mail.com'],
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
            'blog' => \App\Models\Blog::first(),
            'event' => \App\Models\Event::first(), 'participant' => \App\Models\Participant::first(),
            'page' => \App\Models\Page::first(), 'menu' => \App\Models\NavMenu::first(), 'business_unit' => \App\Models\BusinessUnit::first(),
            'member' => \App\Models\Member::first(), 'applicant' => \App\Models\Applicant::first(), 'batch' => \App\Models\Batch::first(),
            'health' => \App\Models\HealthRecord::first(), 'announcement' => \App\Models\Announcement::first(),
            'skema' => \App\Models\Skema::first(), 'module' => \App\Models\Module::first(), 'lesson' => \App\Models\Lesson::first(),
            'progress' => \App\Models\LessonProgress::whereNotNull('submitted_at')->first(),
            'coa' => \App\Models\CoaAccount::first(), 'party' => \App\Models\Party::first(), 'journal' => \App\Models\Journal::where('status', 'draft')->first(),
            'room' => \App\Models\Room::first(), 'duty' => \App\Models\Duty::first(), 'duty_area' => \App\Models\DutyArea::first(),
            'donation' => \App\Models\Donation::first(), 'period' => \App\Models\FiscalPeriod::first(),
            'user' => User::where('email', 'anggota@mail.com')->first(),
        ];
        $user = User::where('email', $email)->firstOrFail();

        foreach (Route::getRoutes() as $route) {
            preg_match_all('/\{(\w+)\}/', $route->uri(), $m);
            if (! in_array('GET', $route->methods()) || ! Str::startsWith($route->uri(), 'dashboard')
                || count($m[1]) !== 1 || ! isset($models[$m[1][0]])) {
                continue;
            }
            $uri = '/' . str_replace('{' . $m[1][0] . '}', $models[$m[1][0]]->getRouteKey(), $route->uri());
            $status = $this->actingAs($user)->get($uri)->getStatusCode();
            $this->assertLessThan(500, $status, "GET {$uri} sebagai {$email} -> {$status}");
        }
    }

    public function test_guest_is_redirected_to_login()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
