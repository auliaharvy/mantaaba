<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use App\Notifications\ApplicantStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Seleksi pendaftar (PRD 9.3): pindah tahap, terima (kuota terkunci), tolak/mundur. */
class AdmissionDecider
{
    public function move(Applicant $applicant, string $status, string $note, ?string $scheduledAt = null): void
    {
        if (! in_array($status, $applicant->nextStages(), true)) {
            throw ValidationException::withMessages(['status' => 'Tahap tidak bisa dipilih dari tahap sekarang.']);
        }
        $applicant->addHistory($status, $note);
        $applicant->status = $status;
        if ($scheduledAt && $status === 'wawancara') {
            $applicant->interview_at = $scheduledAt;
        }
        if ($scheduledAt && $status === 'kunjungan_rumah') {
            $applicant->home_visit_at = $scheduledAt;
        }
        $applicant->save();
        $this->notify($applicant);
    }

    public function reject(Applicant $applicant, string $status, string $note): void
    {
        if ($applicant->isFinal() || ! in_array($status, ['ditolak', 'mengundurkan_diri'], true)) {
            throw ValidationException::withMessages(['status' => 'Pendaftar ini sudah diputuskan.']);
        }
        $applicant->addHistory($status, $note);
        $applicant->fill(['status' => $status, 'decision_note' => $note, 'decided_by' => auth()->id(), 'decided_at' => now()])->save();
        $this->notify($applicant);
    }

    /**
     * Terima: kunci baris angkatan (kuota), buat akun anggota + data anggota, pindahkan berkas.
     * Mengembalikan [member, url atur password].
     */
    public function accept(Applicant $applicant, ?string $note = null): array
    {
        [$member, $user] = DB::transaction(function () use ($applicant, $note) {
            $batch = Batch::whereKey($applicant->batch_id)->lockForUpdate()->first();
            $applicant = Applicant::whereKey($applicant->id)->lockForUpdate()->first();
            if (! $applicant->canBeAccepted()) {
                throw ValidationException::withMessages(['status' => 'Pendaftar hanya bisa diterima dari tahap wawancara atau kunjungan rumah.']);
            }
            if ($batch->acceptedCount() >= $batch->quota) {
                throw ValidationException::withMessages(['status' => 'Kuota angkatan penuh.']);
            }

            $number = Member::nextNumber((int) ($batch->starts_at?->year ?? now()->year));
            // tanpa email: login memakai alamat internal dari nomor anggota; password diatur lewat tautan WA
            $email = $applicant->email && ! User::where('email', $applicant->email)->exists() ? $applicant->email : Str::lower($number) . '@anggota.mantaaba.id';
            $user = User::create(['name' => $applicant->name, 'email' => $email, 'phone_number' => $applicant->phone, 'gender' => $applicant->gender,
                'birthplace' => $applicant->birth_place, 'date_of_birth' => $applicant->birth_date, 'password' => Hash::make(Str::random(40))]);
            $user->assignRole('anggota');

            $member = Member::create([
                'user_id' => $user->id, 'member_number' => $number, 'name' => $applicant->name, 'category' => 'siswa', 'status' => 'aktif', 'phase' => 'inkubasi',
                'batch_id' => $batch->id, 'applicant_id' => $applicant->id, 'joined_at' => $batch->starts_at ?? today(),
            ] + $applicant->only(['nik', 'birth_place', 'birth_date', 'gender', 'address', 'village', 'district', 'regency', 'phone',
                'father_name', 'mother_name', 'guardian_name', 'guardian_phone', 'family_status', 'economic_status']));
            $member->update(['documents' => $this->moveDocuments($applicant, $member), 'photo' => $this->publishPhoto($applicant, $member)]);
            $member->startPhase('inkubasi');

            $applicant->addHistory('diterima', $note);
            $applicant->fill(['status' => 'diterima', 'decision_note' => $note, 'decided_by' => auth()->id(), 'decided_at' => now(), 'member_id' => $member->id])->save();

            return [$member, $user];
        });
        $this->notify($applicant->fresh());

        return [$member, route('password.reset', ['token' => Password::broker()->createToken($user), 'email' => $user->email])];
    }

    /** Pesan WA siap kirim untuk pendaftar (pengurus menekan tautan). */
    public static function whatsappText(Applicant $applicant, ?string $extra = null): string
    {
        return "Assalamu'alaikum {$applicant->name}. " . Applicant::PUBLIC_STATUS[$applicant->status]
            . " (No. {$applicant->registration_number})" . ($extra ? "\n\n{$extra}" : '');
    }

    private function moveDocuments(Applicant $applicant, Member $member): array
    {
        $moved = [];
        foreach ((array) $applicant->documents as $key => $path) {
            $target = "members/{$member->member_number}/" . basename($path);
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->move($path, $target);
            }
            $moved[$key] = $target;
        }
        $applicant->documents = $moved; // pendaftar tetap menunjuk berkas yang sama di folder anggota

        return $moved;
    }

    private function publishPhoto(Applicant $applicant, Member $member): ?string
    {
        $path = $applicant->documents['photo'] ?? null;
        if (! $path || ! Storage::disk('local')->exists($path) || ! in_array(Str::lower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }
        $public = 'members/' . Str::random(32) . '.' . pathinfo($path, PATHINFO_EXTENSION);
        Storage::disk('public')->put($public, Storage::disk('local')->get($path));

        return $public;
    }

    private function notify(Applicant $applicant): void
    {
        if ($applicant->email) {
            Notification::route('mail', $applicant->email)->notify(new ApplicantStatusChanged($applicant));
        }
    }
}
