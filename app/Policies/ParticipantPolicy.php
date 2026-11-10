<?php

namespace App\Policies;

use App\Models\User;

/** Peserta dikelola pemegang permission agenda; peserta sendiri melihat tiketnya. */
class ParticipantPolicy extends ModulePolicy
{
    protected string $module = 'agenda';

    public function view(User $user, $participant): bool
    {
        return $participant->user_id === $user->id || parent::view($user, $participant);
    }
}
