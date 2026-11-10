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

    /** Field gambar (disk public) => lebar maksimum. */
    public const IMAGES = ['logo' => 600, 'logo_footer' => 600, 'favicon' => 256, 'og_image' => 1200, 'qris_image' => 1000];

    /** Template unduhan pendaftaran (disk public; dokumen kosong, bukan data pribadi). */
    public const TEMPLATES = ['parent_consent_template', 'integrity_pact_template'];

    public const COLORS = ['color_primary' => '#5B3A1F', 'color_secondary' => '#C9A04A', 'color_accent' => '#8A2D1E', 'color_surface' => '#F7F1E6'];

    public const DEFAULT_WEIGHTS = ['knowledge' => 20, 'skill' => 25, 'character' => 30, 'social' => 25];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'bank_accounts' => 'array',
        'priority_districts' => 'array',
        'assessment_weights' => 'array',
        'report_signatories' => 'array',
        'accounting_start_date' => 'date',
        'meal_budget_per_person_day' => 'decimal:2',
    ];

    /** Satu baris pengaturan, di-cache sampai diubah. */
    public static function current(): self
    {
        return Cache::rememberForever('site.settings', fn () => static::firstOrCreate([]));
    }

    public function imageUrl(string $field, string $fallback = 'media/logos/logo-mantaaba-512.jpg'): string
    {
        return $this->{$field} ? Storage::disk('public')->url($this->{$field}) : asset($fallback);
    }

    public function whatsappUrl(?string $text = null): ?string
    {
        return self::waLink($this->whatsapp, $text);
    }

    public static function waLink(?string $phone, ?string $text = null): ?string
    {
        $number = preg_replace('/\D/', '', (string) $phone);

        return $number ? 'https://wa.me/' . preg_replace('/^0/', '62', $number) . ($text ? '?text=' . rawurlencode($text) : '') : null;
    }

    /** Rekening dalam satu teks (info pembayaran agenda berbayar sampai M4). */
    public function bankText(): ?string
    {
        return collect($this->bank_accounts)->map(fn ($a) => "{$a['bank']} {$a['number']} a.n. {$a['holder']}")->implode("\n") ?: null;
    }

    public function fullAddress(): string
    {
        return collect([$this->address, $this->village, $this->district, $this->regency, $this->province, $this->postal_code])->filter()->implode(', ');
    }

    /** PRD 7.1: token warna sebagai CSS variable "R G B" agar Tailwind bisa memakai alpha. */
    public function cssVariables(): string
    {
        $rgb = fn (string $hex, float $factor = 1) => implode(' ', array_map(fn ($c) => (int) round(hexdec($c) * $factor), str_split(ltrim($hex, '#'), 2)));
        $color = fn (string $field) => preg_match('/^#[0-9a-f]{6}$/i', (string) $this->{$field}) ? $this->{$field} : self::COLORS[$field];

        return implode(';', [
            '--color-primary:' . $rgb($color('color_primary')),
            '--color-primary-dark:' . $rgb($color('color_primary'), 0.68),
            '--color-secondary:' . $rgb($color('color_secondary')),
            '--color-accent:' . $rgb($color('color_accent')),
            '--color-surface:' . $rgb($color('color_surface')),
        ]);
    }
}
