<?php

namespace App\Services;

use App\Jobs\GenerateCertificatePdf;
use App\Models\Certificate;
use App\Models\Member;
use App\Models\SkemaResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Penerbitan sertifikat (PRD 9.7). Nomor {urut 3}/{seri}/RMS/{bulan romawi}/{tahun}; urut = max+1 per seri per tahun
 * di dalam lock (T1: terbit ulang setelah pencabutan tidak pernah menghasilkan nomor ganda).
 */
class CertificateIssuer
{
    private const ROMAN = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    /** Terbitkan untuk satu hasil skema yang lulus; hasil dikunci. Mengembalikan null bila sudah punya sertifikat valid. */
    public function issueForResult(SkemaResult $result): ?Certificate
    {
        $certificate = DB::transaction(function () use ($result) {
            $result = SkemaResult::whereKey($result->id)->lockForUpdate()->with('enrollment.skema')->first();
            if (! $result->passed) {
                throw ValidationException::withMessages(['result' => 'Sertifikat hanya untuk siswa yang lulus.']);
            }
            if ($result->activeCertificate()->exists()) {
                return null;
            }
            $certificate = $this->create($result->enrollment->member_id, $result->enrollment->skema->code, $result->id);
            $result->update(['locked_at' => now()]);

            return $certificate;
        });
        if ($certificate) {
            GenerateCertificatePdf::dispatch($certificate);
        }

        return $certificate;
    }

    /** Sertifikat Kelulusan Inkubasi (seri INK). */
    public function issueIncubation(Member $member): Certificate
    {
        $certificate = DB::transaction(fn () => $this->create($member->id, Certificate::INCUBATION, null));
        GenerateCertificatePdf::dispatch($certificate);

        return $certificate;
    }

    private function create(int $memberId, string $series, ?int $resultId): Certificate
    {
        $year = (int) now()->year;
        $sequence = (int) Certificate::where('series', $series)->where('year', $year)->lockForUpdate()->max('sequence') + 1;

        return Certificate::create([
            'member_id' => $memberId, 'skema_result_id' => $resultId, 'series' => $series, 'year' => $year, 'sequence' => $sequence,
            'number' => sprintf('%03d/%s/RMS/%s/%d', $sequence, $series, self::ROMAN[(int) now()->month], $year),
            'verification_code' => $this->uniqueCode(),
        ]);
    }

    private function uniqueCode(): string
    {
        do {
            $code = Str::upper(Str::random(12));
        } while (Certificate::where('verification_code', $code)->exists());

        return $code;
    }
}
