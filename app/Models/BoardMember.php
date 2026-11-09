<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class BoardMember extends Model
{
    use FlushesSiteCache, LogsActivity;

    protected $fillable = ['board_period_id', 'user_id', 'division_id', 'position_id', 'sort_order'];

    public function period()
    {
        return $this->belongsTo(BoardPeriod::class, 'board_period_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
