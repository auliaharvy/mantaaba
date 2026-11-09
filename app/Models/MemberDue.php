<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberDue extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['member_id', 'year', 'amount', 'paid_at', 'proof_file', 'recorded_by', 'notes'];

    protected $casts = ['paid_at' => 'date', 'amount' => 'decimal:2'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
