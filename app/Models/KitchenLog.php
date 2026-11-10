<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenLog extends Model
{
    protected $fillable = ['meal_plan_id', 'cooked_at', 'actual_portions', 'leftover_portions', 'cost_total', 'note', 'photo', 'created_by'];

    protected $casts = ['cooked_at' => 'datetime', 'cost_total' => 'decimal:2'];

    public function plan()
    {
        return $this->belongsTo(MealPlan::class, 'meal_plan_id');
    }

    public function moves()
    {
        return $this->morphMany(StockMove::class, 'source');
    }
}
