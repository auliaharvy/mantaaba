<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Konten milik user (blog, thread, comment): pemilik atau moderator (B2).
 * Moderator = pemegang permission `$moderatorPermission`.
 */
abstract class OwnedPolicy extends ModulePolicy
{
    protected string $moderatorPermission;

    public function moderates(User $user): bool
    {
        return $user->can($this->moderatorPermission);
    }

    public function update(User $user, $model): bool
    {
        return parent::update($user, $model) && ($this->owns($user, $model) || $this->moderates($user));
    }

    public function delete(User $user, $model): bool
    {
        return parent::delete($user, $model) && ($this->owns($user, $model) || $this->moderates($user));
    }

    protected function owns(User $user, Model $model): bool
    {
        return (int) $model->user_id === (int) $user->id;
    }
}
