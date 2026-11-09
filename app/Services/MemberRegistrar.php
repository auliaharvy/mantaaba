<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\User;
use App\Notifications\MemberActivation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Membuat anggota = transaksi: akun (atau naikkan akun umum) + data anggota + email aktivasi (PRD 8.2).
 */
class MemberRegistrar
{
    /** @param string|null $passwordHash hash acak bersama untuk import massal (hemat bcrypt per baris) */
    public function register(array $userData, array $memberData, bool $sendActivation = true, ?string $passwordHash = null): Member
    {
        $member = DB::transaction(function () use ($userData, $memberData, $passwordHash) {
            $user = User::where('email', $userData['email'])->lockForUpdate()->first();

            if ($user) {
                if ($user->member()->withTrashed()->exists()) {
                    throw ValidationException::withMessages(['email' => 'Email ini sudah terdaftar sebagai anggota.']);
                }
                $user->fill(array_filter($userData, fn ($v) => $v !== null && $v !== ''))->save();
                if ($user->hasRole('umum') || $user->roles()->doesntExist()) {
                    $before = $user->getRoleNames()->all();
                    $user->syncRoles('anggota');
                    ActivityLog::record('role_changed', $user, ['before' => ['roles' => $before], 'after' => ['roles' => ['anggota']]]);
                }
            } else {
                $user = User::create($userData + ['password' => $passwordHash ?? Hash::make(Str::random(40))]);
                $user->assignRole('anggota');
            }

            return $user->member()->create($memberData);
        });

        if ($sendActivation) {
            $member->user->notify(new MemberActivation);
        }

        return $member;
    }
}
