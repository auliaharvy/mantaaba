<?php

namespace App\Policies;

use App\Models\User;

class CertificatePolicy extends ScopedModulePolicy
{
    protected string $module = 'certificate';

    protected function organizationId($certificate): ?int
    {
        return $certificate->participant->event->organization_id;
    }

    /** Unduh: pemilik (bila belum dicabut) atau pengelola event. */
    public function download(User $user, $certificate): bool
    {
        return ($certificate->participant->user_id === $user->id && $certificate->isValid()) || parent::view($user, $certificate);
    }
}
