<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;

class ForumCategory extends Model
{
    use HasSlug;

    protected string $slugSource = 'name';

    protected $fillable = ['name', 'slug', 'description', 'sort_order'];

    public function threads()
    {
        return $this->hasMany(Thread::class);
    }
}
