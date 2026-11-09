<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Upload ke disk `public` (PRD 5 & 8.5): nama acak, file lama dihapus saat diganti,
 * gambar lebar diperkecil bila ekstensi GD tersedia.
 */
class Upload
{
    public const IMAGE_RULE = 'image|mimes:jpg,jpeg,png,webp|max:3072';

    public const PDF_RULE = 'file|mimes:pdf|max:10240';

    public static function store(UploadedFile $file, string $dir, ?string $old = null, int $maxWidth = 1920): string
    {
        $path = $file->store($dir, 'public');
        self::delete($old);
        self::shrink(Storage::disk('public')->path($path), $maxWidth);

        return $path;
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private static function shrink(string $path, int $maxWidth): void
    {
        if (! extension_loaded('gd') || ! ($info = @getimagesize($path)) || $info[0] <= $maxWidth) {
            return;
        }
        [$load, $save] = match ($info[2]) {
            IMAGETYPE_JPEG => ['imagecreatefromjpeg', fn ($img) => imagejpeg($img, $path, 85)],
            IMAGETYPE_PNG => ['imagecreatefrompng', fn ($img) => imagepng($img, $path, 6)],
            IMAGETYPE_WEBP => ['imagecreatefromwebp', fn ($img) => imagewebp($img, $path, 85)],
            default => [null, null],
        };
        if (! $load || ! ($source = @$load($path))) {
            return;
        }
        $resized = imagescale($source, $maxWidth);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $save($resized);
    }
}
