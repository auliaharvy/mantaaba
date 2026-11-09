<?php

namespace App\Policies;

use App\Models\User;

/** Policy untuk modul tanpa kepemilikan: aksi = permission `{modul}-{aksi}` (PRD 4.3). */
abstract class ModulePolicy
{
    protected string $module;

    public function viewAny(User $user): bool
    {
        return $user->can("{$this->module}-view");
    }

    public function view(User $user, $model): bool
    {
        return $user->can("{$this->module}-view");
    }

    public function create(User $user): bool
    {
        return $user->can("{$this->module}-create");
    }

    public function update(User $user, $model): bool
    {
        return $user->can("{$this->module}-update");
    }

    public function delete(User $user, $model): bool
    {
        return $user->can("{$this->module}-delete");
    }
}
