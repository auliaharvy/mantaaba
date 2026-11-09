<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use LogsActivity;

    protected $fillable = ['participant_id', 'number', 'verification_code', 'file', 'issued_at', 'revoked_at'];

    protected $casts = ['issued_at' => 'datetime', 'revoked_at' => 'datetime'];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function isValid(): bool
    {
        return $this->issued_at !== null && $this->revoked_at === null;
    }
}
