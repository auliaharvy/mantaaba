<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use Illuminate\Database\Eloquent\Model;

class GalleryPhoto extends Model
{
    use FlushesSiteCache;

    protected $fillable = ['gallery_id', 'image', 'caption', 'sort_order'];

    public function gallery()
    {
        return $this->belongsTo(Gallery::class);
    }
}
