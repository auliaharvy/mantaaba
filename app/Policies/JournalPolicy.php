<?php

namespace App\Policies;

use App\Models\User;

/** Voucher (PRD 9.13.2): pembuat (mis. siswa petugas keuangan) boleh mengubah drafnya sendiri. */
class JournalPolicy extends ModulePolicy
{
    protected string $module = 'journal';

    public function update(User $user, $journal): bool
    {
        return $journal->isEditable() && ! $journal->is_system && ($user->can('journal-update') || ($journal->created_by === $user->id && $user->can('journal-create')));
    }

    public function delete(User $user, $journal): bool
    {
        return $journal->isEditable() && ! $journal->is_system && ($user->can('journal-delete') || ($journal->created_by === $user->id && $user->can('journal-create')));
    }
}
