<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealPlan extends Model
{
    public const MEALS = ['sarapan' => 'Sarapan', 'makan_siang' => 'Makan siang', 'makan_malam' => 'Makan malam', 'snack' => 'Snack'];

    protected $fillable = ['date', 'meal', 'menu_ids', 'planned_portions', 'status'];

    protected $casts = ['date' => 'date', 'menu_ids' => 'array'];

    public function log()
    {
        return $this->hasOne(KitchenLog::class);
    }

    public function menus()
    {
        return KitchenMenu::whereIn('id', $this->menu_ids ?? [])->get();
    }

    /** Kebutuhan bahan [item_id => qty] untuk sejumlah porsi. */
    public function requirements(?int $portions = null): array
    {
        $need = [];
        foreach ($this->menus() as $menu) {
            foreach ($menu->ingredients ?? [] as $row) {
                $need[$row['item_id']] = ($need[$row['item_id']] ?? 0) + (float) $row['qty'] * ($portions ?? $this->planned_portions);
            }
        }

        return array_map(fn ($q) => round($q, 3), $need);
    }
}
