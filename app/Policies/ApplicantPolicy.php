<?php

namespace App\Policies;

use App\Models\User;

class ApplicantPolicy extends ModulePolicy
{
    protected string $module = 'admission';

    public function decide(User $user, $applicant): bool
    {
        return $user->can('admission-decide');
    }

    public function export(User $user): bool
    {
        return $user->can('admission-export');
    }
}
