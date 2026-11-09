<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use App\Notifications\RegistrationStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pendaftaran mandiri event (PRD 8.9 alur 2–3):
 * status open & dalam jendela pendaftaran, kuota, audiens, belum terdaftar; biaya 0 -> verified otomatis.
 */
class EventRegistrar
{
    public function register(Event $event, User $user): Participant
    {
        $participant = DB::transaction(function () use ($event, $user) {
            // kunci baris event agar dua pendaftar terakhir tidak sama-sama lolos kuota
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

            $fail = fn (string $message) => throw ValidationException::withMessages(['event' => $message]);
            if (! $event->registrationOpen()) {
                $fail('Pendaftaran event ini sedang tidak dibuka.');
            }
            if ($event->audience === 'anggota' && ! $user->hasRole('anggota')) {
                $fail('Event ini khusus anggota AKP2I.');
            }
            $existing = Participant::where('event_id', $event->id)->where('user_id', $user->id)->first();
            if ($existing && in_array($existing->status, ['pending', 'verified'], true)) {
                $fail('Anda sudah terdaftar di event ini.');
            }
            if ($event->quota !== null && $event->seatsTaken() >= $event->quota) {
                $fail('Kuota penuh.');
            }

            $amount = $event->feeFor($user);
            $free = (float) $amount === 0.0;
            $data = [
                'status' => $free ? 'verified' : 'pending',
                'amount' => $amount,
                'verified_at' => $free ? now() : null,
                'payment_proof' => null,
                'notes' => null,
            ];

            // pendaftaran yang pernah ditolak/dibatalkan dipakai ulang (unique event_id+user_id)
            return $existing
                ? tap($existing)->update($data)
                : Participant::create($data + ['event_id' => $event->id, 'user_id' => $user->id]);
        });

        $user->notify(new RegistrationStatusChanged($participant));

        return $participant;
    }
}
