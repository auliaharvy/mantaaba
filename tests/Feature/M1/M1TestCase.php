<?php

namespace Tests\Feature\M1;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class M1TestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    protected function adminPd(): User
    {
        return $this->user('admin@mail.com');
    }

    protected function adminPc(): User
    {
        return $this->user('adminpc@mail.com');
    }

    /** PC milik admin PC demo. */
    protected function ownPc(): Organization
    {
        return $this->adminPc()->organization;
    }

    protected function otherPc(): Organization
    {
        return Organization::pc()->whereKeyNot($this->ownPc()->id)->firstOrFail();
    }

    protected function dt(string $route, array $params = [], array $columns = ['DT_RowIndex', 'member_number', 'user.name']): array
    {
        $query = $params + ['draw' => 1, 'start' => 0, 'length' => 100, 'search' => ['value' => ''], 'order' => [['column' => 1, 'dir' => 'asc']]];
        foreach ($columns as $i => $c) {
            $query['columns'][$i] = ['data' => $c, 'name' => $c, 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']];
        }

        return $this->getJson(route($route, $query), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json();
    }
}
