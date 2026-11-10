<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    use LogsActivity;

    protected $fillable = ['warehouse_id', 'date', 'status', 'lines', 'note', 'created_by'];

    protected $casts = ['date' => 'date', 'lines' => 'array', 'finalized_at' => 'datetime'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
