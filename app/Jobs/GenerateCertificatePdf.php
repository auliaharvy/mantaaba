<?php

namespace App\Jobs;

use App\Models\Certificate;
use App\Models\Setting;
use App\Models\Skema;
use App\Notifications\CertificateIssued;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/** Render PDF satu sertifikat (A4 landscape) ke disk privat `local`, lalu beri tahu anggota (PRD 9.7). */
class GenerateCertificatePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Certificate $certificate)
    {
    }

    public function handle(): void
    {
        $certificate = $this->certificate->load('member.batch', 'result.enrollment.skema.modules', 'result.enrollment.skema.units');
        $skema = $certificate->result?->enrollment?->skema;
        $verifyUrl = route('site.certificate', $certificate->verification_code);
        $site = Setting::current();
        $signatories = $skema?->certificate_signatories
            ?: collect($site->report_signatories ?? [])->filter()->map(fn ($name, $role) => ['name' => $name, 'title' => ucfirst($role) . ' ' . $site->foundation_name])->values()->all();

        $pdf = Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'member' => $certificate->member,
            'skema' => $skema,
            'result' => $certificate->result,
            // kelulusan inkubasi: rangkuman nilai kelima skema
            'skemaResults' => $certificate->isIncubation()
                ? Skema::ordered()->get()->map(fn ($s) => [$s, $certificate->member->enrollments()->where('skema_id', $s->id)->first()?->result])
                : collect(),
            'site' => $site,
            'verifyUrl' => $verifyUrl,
            'qr' => 'data:image/svg+xml;base64,' . base64_encode(QrCode::format('svg')->size(150)->margin(0)->generate($verifyUrl)),
            'background' => self::dataUri($skema?->certificate_template),
            'signatories' => collect($signatories)->filter(fn ($s) => filled($s['name'] ?? null))->take(2)
                ->map(fn ($s) => $s + ['signature_uri' => self::dataUri($s['signature'] ?? null)])->values(),
        ])->setPaper('a4', 'landscape');

        $path = "certificates/{$certificate->series}/{$certificate->verification_code}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        $firstIssue = $certificate->issued_at === null;
        $certificate->update(['file' => $path, 'issued_at' => $certificate->issued_at ?? now()]);

        if ($firstIssue && $certificate->member->user) {
            $certificate->member->user->notify(new CertificateIssued($certificate));
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
