<?php

namespace App\Policies;

use App\Models\User;

class MemberDuePolicy extends ScopedModulePolicy
{
    protected string $module = 'due';

    protected function organizationId($due): ?int
    {
        return $due->member->organization_id;
    }

    /** PRD 8.3 AC: bukti bayar hanya untuk admin PD (pencatat iuran) dan pemiliknya. */
    public function viewProof(User $user, $due): bool
    {
        return $due->member->user_id === $user->id || $user->can('due-update');
    }
}
