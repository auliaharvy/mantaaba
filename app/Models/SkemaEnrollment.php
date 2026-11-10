<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Keikutsertaan anggota pada satu skema (PRD 9.6). */
class SkemaEnrollment extends Model
{
    public const STATUSES = ['terkunci' => 'Terkunci', 'berjalan' => 'Berjalan', 'lulus' => 'Lulus', 'belum_lulus' => 'Belum lulus'];

    protected $fillable = ['member_id', 'skema_id', 'opened_at', 'status'];

    protected $casts = ['opened_at' => 'datetime'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function skema()
    {
        return $this->belongsTo(Skema::class);
    }

    public function result()
    {
        return $this->hasOne(SkemaResult::class, 'enrollment_id');
    }

    public function isLocked(): bool
    {
        return $this->status === 'terkunci';
    }

    /** Persentase materi terbit yang sudah diselesaikan pemilik. */
    public function progressPercent(): int
    {
        $lessonIds = Lesson::where('is_published', true)->whereIn('module_id', Module::where('skema_id', $this->skema_id)->select('id'))->pluck('id');
        if ($lessonIds->isEmpty() || ! $this->member->user_id) {
            return 0;
        }
        $done = LessonProgress::where('user_id', $this->member->user_id)->whereIn('lesson_id', $lessonIds)->whereNotNull('completed_at')->count();

        return (int) round($done / $lessonIds->count() * 100);
    }
}
