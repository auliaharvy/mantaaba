<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * B12: semua DataTable server-side. Request dibangun dari definisi kolom JS
 * di blade: tanpa cari + urut kolom 0 (default JS), lalu cari "a" + urut tiap kolom.
 */
class DatatableServerSideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @return array<string, string[]> route => nama kolom */
    private function tables(): array
    {
        $tables = [];
        foreach (Finder::create()->in(resource_path('views/dashboard'))->name('*.blade.php') as $file) {
            preg_match_all("/ajax:\s*\"\{\{\s*route\('([^']+)'\)\s*\}\}\"(.*?)\]/s", $file->getContents(), $m, PREG_SET_ORDER);
            foreach ($m as [, $route, $cols]) {
                preg_match_all("/data:\s*'([^']+)'/", $cols, $c);
                $tables[$route] = $c[1];
            }
        }

        return $tables;
    }

    public function test_every_datatable_handles_search_and_sort_server_side()
    {
        $admin = User::where('email', 'superadmin@mail.com')->firstOrFail();
        $tables = $this->tables();
        $this->assertNotEmpty($tables);

        foreach ($tables as $route => $columns) {
            if (! Route::has($route)) {
                continue;
            }
            $cols = array_map(fn ($c) => ['data' => $c, 'name' => $c, 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']], $columns);
            $cases = [['', 0], ...array_map(fn ($i) => ['a', $i], array_keys($columns))];

            foreach ($cases as [$search, $orderBy]) {
                $query = [
                    'draw' => 1, 'start' => 0, 'length' => 5, 'columns' => $cols,
                    'search' => ['value' => $search, 'regex' => 'false'],
                    'order' => [['column' => $orderBy, 'dir' => 'asc']],
                ];
                $label = "{$route} cari='{$search}' urut={$columns[$orderBy]}";
                $response = $this->actingAs($admin)->getJson(route($route, $query), ['X-Requested-With' => 'XMLHttpRequest']);

                $this->assertSame(200, $response->status(), "{$label}: " . substr($response->getContent(), 0, 300));
                $this->assertArrayHasKey('recordsFiltered', $response->json(), $label);
                // yajra menelan error SQL: tetap 200 tapi 0 baris + key 'error'
                $this->assertArrayNotHasKey('error', $response->json(), "{$label}: " . substr((string) $response->json('error'), 0, 300));
                if ($search === '') {
                    $this->assertSame($response->json('recordsTotal'), $response->json('recordsFiltered'), $label);
                }
            }
        }
    }
}
