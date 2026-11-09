<?php

namespace App\Policies;

use App\Models\User;

/** Peserta dikelola pemegang permission agenda; peserta sendiri melihat tiket & bukti bayarnya. */
class ParticipantPolicy extends ModulePolicy
{
    protected string $module = 'agenda';

    public function view(User $user, $participant): bool
    {
        return $participant->user_id === $user->id || parent::view($user, $participant);
    }

    /** Bukti bayar: file privat lewat route ber-policy. */
    public function viewProof(User $user, $participant): bool
    {
        return $participant->user_id === $user->id || parent::update($user, $participant);
    }

    public function uploadProof(User $user, $participant): bool
    {
        return $participant->user_id === $user->id && $participant->status === 'pending';
    }
}
