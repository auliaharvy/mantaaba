<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateCertificatePdf;
use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Unduh, cabut, terbitkan ulang sertifikat (PRD 8.9). */
class CertificateController extends Controller
{
    public function download(Certificate $certificate)
    {
        $this->authorize('download', $certificate);
        abort_unless($certificate->file && Storage::disk('local')->exists($certificate->file), 404, 'Sertifikat sedang diproses.');

        return Storage::disk('local')->download($certificate->file, 'sertifikat-' . Str::slug($certificate->participant->event->title) . '.pdf');
    }

    public function revoke(Certificate $certificate)
    {
        $this->authorize('update', $certificate);
        $certificate->update(['revoked_at' => now()]);

        return back()->with('success', "Sertifikat {$certificate->number} dicabut.");
    }

    public function reissue(Certificate $certificate)
    {
        $this->authorize('update', $certificate);
        GenerateCertificatePdf::dispatch($certificate); // nomor & kode verifikasi tetap; revoked_at dikosongkan

        return back()->with('success', "Sertifikat {$certificate->number} sedang diterbitkan ulang.");
    }
}
