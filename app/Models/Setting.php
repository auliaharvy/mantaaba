<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use FlushesSiteCache, HasFactory;

    public const IMAGES = ['logo', 'logo_footer', 'favicon'];

    protected $fillable = [
        'site_name', 'tagline', 'email', 'phone', 'whatsapp', 'address', 'maps_embed',
        'facebook', 'instagram', 'youtube', 'linkedin',
        'url_registration', 'url_member_service', 'url_ejournal', 'bank_account_info',
        'logo', 'logo_footer', 'favicon', 'dues_amounts',
    ];

    protected $casts = ['dues_amounts' => 'array'];

    /** Satu baris pengaturan, di-cache sampai diubah. */
    public static function current(): self
    {
        return Cache::rememberForever('site.settings', fn () => static::firstOrCreate([], ['email' => 'sekretariat@akp2i-jabar.id']));
    }

    public function imageUrl(string $field, string $fallback = 'media/logos/akp2i.png'): string
    {
        return $this->{$field} ? Storage::disk('public')->url($this->{$field}) : asset($fallback);
    }

    public function whatsappUrl(): ?string
    {
        $number = preg_replace('/\D/', '', (string) $this->whatsapp);

        return $number ? 'https://wa.me/' . preg_replace('/^0/', '62', $number) : null;
    }
}
