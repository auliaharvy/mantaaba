<?php

namespace App\Jobs;

use App\Models\Certificate;
use App\Notifications\CertificateIssued;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/** Render PDF satu sertifikat (A4 landscape) ke disk privat `local`, lalu email peserta. */
class GenerateCertificatePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Certificate $certificate)
    {
    }

    public function handle(): void
    {
        $certificate = $this->certificate->load('participant.user', 'participant.event');
        $event = $certificate->participant->event;
        $verifyUrl = route('site.certificate', $certificate->verification_code);

        $pdf = Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'participant' => $certificate->participant,
            'event' => $event,
            'verifyUrl' => $verifyUrl,
            'qr' => 'data:image/svg+xml;base64,' . base64_encode(QrCode::format('svg')->size(150)->margin(0)->generate($verifyUrl)),
            'background' => self::dataUri($event->certificate_template),
            'signatories' => collect($event->certificate_signatory ?? [])->filter(fn ($s) => filled($s['name'] ?? null))
                ->map(fn ($s) => $s + ['signature_uri' => self::dataUri($s['signature'] ?? null)])->values(),
        ])->setPaper('a4', 'landscape');

        $path = "certificates/{$event->id}/{$certificate->verification_code}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        $firstIssue = $certificate->issued_at === null;
        $certificate->update(['file' => $path, 'issued_at' => $certificate->issued_at ?? now(), 'revoked_at' => null]);

        if ($firstIssue) {
            $certificate->participant->user->notify(new CertificateIssued($certificate));
        }
    }

    /** Gambar dari disk public sebagai data URI (dompdf tidak perlu akses berkas/URL luar). */
    private static function dataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return 'data:' . Storage::disk('public')->mimeType($path) . ';base64,' . base64_encode(Storage::disk('public')->get($path));
    }
}
