<?php

namespace App\Models;

use App\Support\Blocks;
use Illuminate\Database\Eloquent\Model;

/** Materi (PRD 9.6): teks, video YouTube, file, atau tugas. */
class Lesson extends Model
{
    public const TYPES = ['text' => 'Teks', 'video' => 'Video', 'file' => 'File', 'assignment' => 'Tugas'];

    protected $fillable = ['module_id', 'title', 'type', 'body', 'video_url', 'file', 'due_days', 'is_published', 'sort_order'];

    protected $casts = ['is_published' => 'boolean'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function progress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function embedUrl(): ?string
    {
        return Blocks::embedUrl($this->video_url);
    }

    public function isAssignment(): bool
    {
        return $this->type === 'assignment';
    }
}
