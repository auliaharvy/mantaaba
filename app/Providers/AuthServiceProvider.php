<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot()
    {
        $this->registerPolicies();

        // PRD 5.2: super admin lolos semua permission; aturan pemisahan tugas dicek eksplisit di service (bukan lewat Gate)
        Gate::before(fn ($user, $ability) => $user->hasRole('super_admin') ? true : null);
    }
}
