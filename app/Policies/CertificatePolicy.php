<?php

namespace App\Policies;

use App\Models\User;

/** Sertifikat (PRD 9.7): terbit/cabut lewat permission khusus; pemilik mengunduh miliknya. */
class CertificatePolicy extends ModulePolicy
{
    protected string $module = 'assessment';

    /** Unduh: pemilik (bila belum dicabut) atau pemegang assessment-view. */
    public function download(User $user, $certificate): bool
    {
        return ($certificate->member->user_id === $user->id && $certificate->isValid()) || parent::view($user, $certificate);
    }

    public function issue(User $user): bool
    {
        return $user->can('certificate-issue');
    }

    public function revoke(User $user, $certificate): bool
    {
        return $user->can('certificate-revoke');
    }
}
