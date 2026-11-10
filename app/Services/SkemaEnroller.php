<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Member;
use App\Models\Skema;
use App\Models\SkemaEnrollment;

/** Pendaftaran siswa ke skema (PRD 9.2, 9.6). */
class SkemaEnroller
{
    /** Angkatan berjalan: semua siswa didaftarkan ke semua skema; skema pertama langsung dibuka. */
    public function enrollBatch(Batch $batch): int
    {
        $count = 0;
        $batch->members()->where('category', 'siswa')->where('status', 'aktif')->each(function (Member $member) use (&$count) {
            $count += $this->enrollMember($member);
        });

        return $count;
    }

    public function enrollMember(Member $member): int
    {
        $created = 0;
        foreach (Skema::ordered()->get() as $i => $skema) {
            $enrollment = SkemaEnrollment::firstOrCreate(['member_id' => $member->id, 'skema_id' => $skema->id], ['status' => $i === 0 ? 'berjalan' : 'terkunci', 'opened_at' => $i === 0 ? now() : null]);
            $created += $enrollment->wasRecentlyCreated ? 1 : 0;
        }

        return $created;
    }

    /** Buka satu skema untuk seluruh siswa angkatan sekaligus. */
    public function openSkema(Batch $batch, Skema $skema): int
    {
        return SkemaEnrollment::where('skema_id', $skema->id)->where('status', 'terkunci')
            ->whereIn('member_id', $batch->members()->select('id'))
            ->update(['status' => 'berjalan', 'opened_at' => now()]);
    }
}
