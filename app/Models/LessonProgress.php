<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Progres materi & pengumpulan tugas (PRD 9.6). */
class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    protected $fillable = ['user_id', 'lesson_id', 'completed_at', 'submission_file', 'submission_text', 'submitted_at', 'feedback', 'reviewed_by', 'reviewed_at', 'score'];

    protected $casts = ['completed_at' => 'datetime', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
