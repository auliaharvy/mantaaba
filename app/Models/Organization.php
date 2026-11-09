<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Organization extends Model
{
    use FlushesSiteCache, HasFactory, SoftDeletes;

    public const TYPES = ['pusat' => 'Pengurus Pusat', 'pd' => 'Pengurus Daerah', 'pc' => 'Pengurus Cabang'];

    protected $fillable = ['parent_id', 'type', 'name', 'slug', 'code', 'city', 'address', 'phone', 'email', 'profile', 'greeting', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (Organization $org) {
            $org->slug ??= Str::slug($org->name);
        });
    }

    /** PD aktif aplikasi (PRD 8.1): dari config, atau PD pertama bila belum diisi. */
    public static function active(): self
    {
        return static::find(config('akp2i.organization_id')) ?? static::where('type', 'pd')->orderBy('id')->firstOrFail();
    }

    public function parent()
    {
        return $this->belongsTo(Organization::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Organization::class, 'parent_id');
    }

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function boardPeriods()
    {
        return $this->hasMany(BoardPeriod::class);
    }

    public function activeBoardPeriod()
    {
        return $this->hasOne(BoardPeriod::class)->where('is_active', true);
    }

    public function scopePc($query)
    {
        return $query->where('type', 'pc');
    }

    /** PD aktif + PC turunannya. */
    public function scopeInActiveRegion($query)
    {
        $pd = static::active();

        return $query->where(fn ($q) => $q->whereKey($pd->id)->orWhere('parent_id', $pd->id));
    }
}
