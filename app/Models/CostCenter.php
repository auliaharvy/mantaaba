<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Pusat biaya (PRD 9.13.1); unit bisnis mendapat pusat biaya otomatis. */
class CostCenter extends Model
{
    public const TYPES = ['program' => 'Program', 'operasional' => 'Operasional', 'unit_bisnis' => 'Unit bisnis', 'umum' => 'Umum'];

    protected $fillable = ['code', 'name', 'type', 'business_unit_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected $attributes = ['is_active' => true];

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public static function forUnit(BusinessUnit $unit): self
    {
        return static::firstOrCreate(['business_unit_id' => $unit->id], [
            'code' => 'UB-' . str_pad((string) $unit->id, 3, '0', STR_PAD_LEFT), 'name' => $unit->name, 'type' => 'unit_bisnis',
        ]);
    }

    public static function byCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
