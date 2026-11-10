<?php

namespace App\Console\Commands;

use App\Models\Applicant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** PRD 9.3/10: data pendaftar ditolak/mundur dihapus permanen beserta berkas 12 bulan setelah keputusan [TBD Q8]. */
class PurgeRejectedApplicants extends Command
{
    protected $signature = 'applicants:purge {--months=12}';

    protected $description = 'Hapus permanen data & berkas pendaftar yang ditolak/mengundurkan diri lebih dari N bulan lalu';

    public function handle(): int
    {
        $count = 0;
        Applicant::withTrashed()->whereIn('status', ['ditolak', 'mengundurkan_diri'])
            ->where('decided_at', '<', now()->subMonths((int) $this->option('months')))
            ->each(function (Applicant $applicant) use (&$count) {
                Storage::disk('local')->deleteDirectory("applicants/{$applicant->registration_number}");
                $applicant->forceDelete();
                $count++;
            });
        $this->info("{$count} pendaftar dihapus.");

        return self::SUCCESS;
    }
}
