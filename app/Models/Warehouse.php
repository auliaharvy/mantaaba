<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['code', 'name', 'cost_center_id', 'pic_user_id'];

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public static function byCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
