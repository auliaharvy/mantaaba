<?php

namespace App\Policies;

use App\Models\User;

/** ModulePolicy + "scope PC" (PRD 4.3): admin PC hanya melihat data organisasinya. */
abstract class ScopedModulePolicy extends ModulePolicy
{
    abstract protected function organizationId($model): ?int;

    protected function inScope(User $user, $model): bool
    {
        $ids = $user->scopedOrganizationIds();

        return $ids === null || in_array($this->organizationId($model), $ids, true);
    }

    public function view(User $user, $model): bool
    {
        return parent::view($user, $model) && $this->inScope($user, $model);
    }

    public function update(User $user, $model): bool
    {
        return parent::update($user, $model) && $this->inScope($user, $model);
    }

    public function delete(User $user, $model): bool
    {
        return parent::delete($user, $model) && $this->inScope($user, $model);
    }
}
