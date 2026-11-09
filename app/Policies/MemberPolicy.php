<?php

namespace App\Policies;

class MemberPolicy extends ScopedModulePolicy
{
    protected string $module = 'member';

    protected function organizationId($member): ?int
    {
        return $member->organization_id;
    }
}
