<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\User;
use App\Notifications\SuspensionCandidatesReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/** Job harian PRD 8.3: beri tahu admin PD bila ada usulan skors. Tidak mengubah status. */
class SuspensionCandidates extends Command
{
    protected $signature = 'members:suspension-candidates';

    protected $description = 'Kirim ringkasan anggota aktif yang menunggak iuran ke admin PD';

    public function handle(): int
    {
        $count = Member::where('status', 'aktif')->duesStatus('menunggak')->count();
        $this->info("Usulan skors: {$count} anggota.");

        if ($count > 0) {
            Notification::send(User::role('admin_pd')->where('is_active', true)->get(), new SuspensionCandidatesReport($count));
        }

        return self::SUCCESS;
    }
}
