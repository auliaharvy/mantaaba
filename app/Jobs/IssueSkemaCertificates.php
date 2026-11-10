<?php

namespace App\Jobs;

use App\Models\Batch;
use App\Models\Skema;
use App\Models\SkemaResult;
use App\Services\CertificateIssuer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Terbitkan sertifikat untuk semua siswa lulus satu skema di satu angkatan (PRD 9.7), lewat queue. */
class IssueSkemaCertificates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Batch $batch, public Skema $skema)
    {
    }

    public function handle(CertificateIssuer $issuer): void
    {
        SkemaResult::where('passed', true)
            ->whereHas('enrollment', fn ($q) => $q->where('skema_id', $this->skema->id)->whereIn('member_id', $this->batch->members()->select('id')))
            ->whereDoesntHave('activeCertificate')
            ->each(fn (SkemaResult $result) => $issuer->issueForResult($result));
    }
}
