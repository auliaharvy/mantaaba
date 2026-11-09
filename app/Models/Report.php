<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = ['reportable_type', 'reportable_id', 'user_id', 'reason', 'resolved_at', 'resolved_by'];

    protected $casts = ['resolved_at' => 'datetime'];

    public function reportable()
    {
        return $this->morphTo()->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
