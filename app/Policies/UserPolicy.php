<?php

namespace App\Policies;

use App\Models\User;

/** Pengguna aplikasi (PRD 8.10). B4: admin non-SA tidak menyentuh SA / admin PD. */
class UserPolicy extends ModulePolicy
{
    protected string $module = 'user';

    public function view(User $user, $target): bool
    {
        return parent::view($user, $target) && $user->canManage($target);
    }

    public function update(User $user, $target): bool
    {
        return parent::update($user, $target) && $user->canManage($target);
    }

    public function delete(User $user, $target): bool
    {
        // super admin tidak bisa dihapus, oleh siapa pun (aturan baraasa)
        return ! $target->hasRole('super_administrator')
            && parent::delete($user, $target) && $user->canManage($target);
    }
}
