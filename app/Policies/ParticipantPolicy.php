<?php

namespace App\Policies;

use App\Models\User;

/** Peserta dikelola pengelola event (scope PC); peserta sendiri melihat tiket & bukti bayarnya. */
class ParticipantPolicy extends ScopedModulePolicy
{
    protected string $module = 'participant';

    protected function organizationId($participant): ?int
    {
        return $participant->event->organization_id;
    }

    public function view(User $user, $participant): bool
    {
        return $participant->user_id === $user->id || parent::view($user, $participant);
    }

    /** Bukti bayar (PRD 9: file privat lewat route ber-policy). */
    public function viewProof(User $user, $participant): bool
    {
        return $participant->user_id === $user->id || parent::update($user, $participant);
    }

    public function uploadProof(User $user, $participant): bool
    {
        return $participant->user_id === $user->id && $participant->status === 'pending';
    }
}
