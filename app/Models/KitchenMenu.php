<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenMenu extends Model
{
    public const CATEGORIES = ['pokok' => 'Makanan pokok', 'lauk' => 'Lauk', 'sayur' => 'Sayur', 'pelengkap' => 'Pelengkap', 'minuman' => 'Minuman', 'buah' => 'Buah'];

    protected $fillable = ['name', 'category', 'ingredients', 'notes', 'is_active'];

    protected $casts = ['ingredients' => 'array', 'is_active' => 'boolean'];
}
