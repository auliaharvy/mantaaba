<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Pendaftaran agenda publik (PRD 9.8): pengguna login atau tamu (nama, WA, email). Kuota dikunci per baris agenda. */
class EventRegistrar
{
    public function register(Event $event, ?User $user, array $guest = []): Participant
    {
        return DB::transaction(function () use ($event, $user, $guest) {
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            $fail = fn (string $message) => throw ValidationException::withMessages(['event' => $message]);
            if (! $event->registrationOpen()) {
                $fail('Pendaftaran agenda ini sedang tidak dibuka.');
            }
            $existing = $user
                ? $event->participants()->where('user_id', $user->id)->first()
                : $event->participants()->whereNull('user_id')->where('guest_phone', $guest['guest_phone'])->where('status', '!=', 'batal')->first();
            if ($existing && $existing->status !== 'batal') {
                $fail('Anda sudah terdaftar di agenda ini.');
            }
            if ($event->quota !== null && $event->seatsTaken() >= $event->quota) {
                $fail('Kuota penuh.');
            }

            // pendaftaran pengguna yang pernah batal dipakai ulang (unique event_id+user_id)
            return $existing
                ? tap($existing)->update(['status' => 'terdaftar'])
                : $event->participants()->create($user ? ['user_id' => $user->id] : $guest);
        });
    }
}
