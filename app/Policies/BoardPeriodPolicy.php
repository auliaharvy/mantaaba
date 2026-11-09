<?php

namespace App\Policies;

class BoardPeriodPolicy extends ScopedModulePolicy
{
    protected string $module = 'board';

    protected function organizationId($period): ?int
    {
        return $period->organization_id;
    }
}
