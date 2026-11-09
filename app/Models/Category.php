<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasSlug;

    protected string $slugSource = 'name';

    protected $fillable = ['name', 'slug'];

    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }
}
