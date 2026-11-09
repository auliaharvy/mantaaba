<?php

namespace App\Jobs;

use App\Models\Certificate;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * PRD 8.9 alur 7: beri nomor sertifikat untuk semua peserta yang HADIR, lalu satu job PDF per peserta.
 * Request admin hanya men-dispatch job ini, jadi 300+ sertifikat tidak membuat request timeout.
 */
class IssueEventCertificates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Event $event)
    {
    }

    public function handle(): void
    {
        $attendees = $this->event->participants()->where('status', 'verified')->whereNotNull('attended_at')->orderBy('id')->get();
        $year = $this->event->starts_at?->year ?? now()->year;

        foreach ($attendees as $i => $participant) {
            $certificate = $participant->certificateRecord;
            if ($certificate?->isValid()) {
                continue; // sudah terbit; terbitkan ulang lewat tombol per sertifikat
            }
            // {urut 3 digit}/SERT/{kode agenda}/RMS/{tahun}; diganti format per skema di M3 (PRD 9.7)
            $certificate ??= Certificate::create([
                'participant_id' => $participant->id,
                'number' => sprintf('%03d/SERT/%s/RMS/%d', $i + 1, $this->event->code, $year),
                'verification_code' => $this->uniqueCode(),
            ]);
            GenerateCertificatePdf::dispatch($certificate);
        }
    }

    private function uniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(12));
        } while (Certificate::where('verification_code', $code)->exists());

        return $code;
    }
}
