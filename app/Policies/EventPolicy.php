<?php

namespace App\Policies;

class EventPolicy extends ScopedModulePolicy
{
    protected string $module = 'event';

    protected function organizationId($event): ?int
    {
        return $event->organization_id;
    }
}
