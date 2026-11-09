<?php

namespace App\Policies;

use App\Models\User;

/** Pengguna (PRD 9.18): non-SA tidak menyentuh akun super admin; hak akses hanya oleh SA. */
class UserPolicy extends ModulePolicy
{
    protected string $module = 'user';

    public function update(User $user, $target): bool
    {
        return parent::update($user, $target) && $user->canManage($target);
    }

    public function managePermissions(User $user, $target): bool
    {
        return $user->isSuperAdmin() && ! $target->isSuperAdmin();
    }
}
