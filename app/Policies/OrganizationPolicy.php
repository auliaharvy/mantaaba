<?php

namespace App\Policies;

use App\Models\User;

class OrganizationPolicy extends ScopedModulePolicy
{
    protected string $module = 'organization';

    protected function organizationId($organization): ?int
    {
        return $organization->id;
    }

    /** Hanya PC yang bisa dihapus, dan hanya bila tidak punya anggota (PRD 8.1). */
    public function delete(User $user, $organization): bool
    {
        return $organization->type === 'pc' && parent::delete($user, $organization);
    }
}
