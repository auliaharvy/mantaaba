<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/** Slug dibuat sekali saat dibuat, tidak berubah saat judul/nama diedit (PRD 8.6: tautan tidak putus). */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (blank($model->slug)) {
                $base = Str::slug($model->{$model->slugSource ?? 'title'}) ?: 'item';
                $slug = $base;
                while (static::withoutGlobalScopes()->where('slug', $slug)->exists()) {
                    $slug = $base . '-' . Str::lower(Str::random(4));
                }
                $model->slug = $slug;
            }
        });
    }
}
