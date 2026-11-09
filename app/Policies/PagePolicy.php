<?php

namespace App\Policies;

class PagePolicy extends ModulePolicy
{
    protected string $module = 'page';

    /** PRD 8.5: halaman slug tetap tidak bisa dihapus. */
    public function delete(\App\Models\User $user, $page): bool
    {
        return false;
    }
}
