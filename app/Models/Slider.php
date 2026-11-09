<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    use FlushesSiteCache;

    protected $fillable = ['title', 'subtitle', 'image', 'button_text', 'button_url', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
