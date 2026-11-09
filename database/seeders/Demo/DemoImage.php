<?php

namespace Database\Seeders\Demo;

use Illuminate\Support\Facades\Storage;

/**
 * Grafis demo berbasis GD: poster event/berita (foto + overlay hijau AKP2I + judul),
 * template sertifikat, dan bukti transfer. Hanya dipakai DemoSeeder.
 */
class DemoImage
{
    private const ASSETS = __DIR__ . '/../demo-assets';

    /** Foto dirujuk dengan namanya (mis. "ballroom"); awalan nomor pada nama file diabaikan. */
    public static function photoPath(string $name): string
    {
        $name = preg_replace('/^\d+-/', '', pathinfo($name, PATHINFO_FILENAME));
        $found = glob(self::ASSETS . "/photos/*-{$name}.jpg");

        return $found[0] ?? throw new \InvalidArgumentException("Foto demo tidak ditemukan: {$name}");
    }

    /** Salin foto stok ke disk public (dipakai galeri & slider). */
    public static function storePhoto(string $file, string $dir): string
    {
        $path = "{$dir}/" . pathinfo($file, PATHINFO_FILENAME) . '-' . substr(md5($dir . $file), 0, 6) . '.jpg';
        Storage::disk('public')->put($path, file_get_contents(self::photoPath($file)));

        return $path;
    }

    /** Poster 1600x1000 (16:10): foto latar + overlay, label, judul, keterangan, identitas PD. */
    public static function poster(string $photo, string $label, string $title, string $meta, string $dir): string
    {
        [$w, $h] = [1600, 1000];
        $img = self::cover(self::photoPath($photo), $w, $h);

        // overlay hijau gelap 70% + gradien ke kiri agar teks terbaca
        for ($x = 0; $x < $w; $x += 4) {
            $alpha = (int) (25 + 45 * ($x / $w)); // 127 = transparan penuh; kiri lebih gelap untuk teks
            imagefilledrectangle($img, $x, 0, $x + 3, $h, imagecolorallocatealpha($img, 0, 45, 20, $alpha));
        }
        $white = imagecolorallocate($img, 255, 255, 255);
        $green = imagecolorallocate($img, 0, 146, 63);
        $pale = imagecolorallocate($img, 214, 240, 223);

        // label (jenis event / kategori)
        $bold = self::font('Bold');
        $box = imagettfbbox(26, 0, $bold, mb_strtoupper($label));
        imagefilledrectangle($img, 90, 120, 90 + ($box[2] - $box[0]) + 44, 176, $green);
        imagettftext($img, 26, 0, 112, 160, $white, $bold, mb_strtoupper($label));

        // judul (dibungkus maks 4 baris)
        $y = 280;
        foreach (array_slice(self::wrap($title, $bold, 64, 1300), 0, 4) as $line) {
            imagettftext($img, 64, 0, 90, $y, $white, $bold, $line);
            $y += 92;
        }
        imagettftext($img, 30, 0, 90, $y + 30, $pale, self::font('SemiBold'), $meta);

        // identitas
        imagefilledrectangle($img, 0, $h - 110, $w, $h, imagecolorallocatealpha($img, 0, 45, 20, 20));
        $emblem = imagecreatefrompng(public_path('media/logos/akp2i-icon.png'));
        imagecopyresampled($img, $emblem, 86, $h - 98, 0, 0, 86, 86, imagesx($emblem), imagesy($emblem));
        imagettftext($img, 28, 0, 190, $h - 45, $white, $bold, 'PD AKP2I JAWA BARAT');

        return self::save($img, $dir);
    }

    /** Template sertifikat A4 landscape (1754x1240): bingkai ganda, ornamen sudut, watermark. */
    public static function certificateTemplate(string $dir): string
    {
        [$w, $h] = [1754, 1240];
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        $dark = imagecolorallocate($img, 0, 45, 20);
        $green = imagecolorallocate($img, 0, 146, 63);
        $gold = imagecolorallocate($img, 196, 160, 72);
        $soft = imagecolorallocatealpha($img, 0, 146, 63, 121);

        imagesetthickness($img, 18);
        imagerectangle($img, 40, 40, $w - 40, $h - 40, $dark);
        imagesetthickness($img, 4);
        imagerectangle($img, 72, 72, $w - 72, $h - 72, $gold);
        foreach ([[72, 72], [$w - 72, 72], [72, $h - 72], [$w - 72, $h - 72]] as [$cx, $cy]) {
            imagefilledellipse($img, $cx, $cy, 90, 90, $green);
            imagefilledellipse($img, $cx, $cy, 50, 50, $gold);
        }
        // pita bawah & watermark
        imagefilledrectangle($img, 72, $h - 96, $w - 72, $h - 90, $green);
        imagettftext($img, 220, 0, (int) ($w / 2 - 330), (int) ($h / 2 + 90), $soft, self::font('Bold'), 'AKP2I');

        return self::save($img, $dir, 'png');
    }

    /** Bukti transfer sederhana (untuk iuran & pendaftaran berbayar). */
    public static function receipt(string $name, int $amount, string $date, string $dir): string
    {
        $img = imagecreatetruecolor(720, 960);
        imagefill($img, 0, 0, imagecolorallocate($img, 248, 250, 255));
        $dark = imagecolorallocate($img, 30, 41, 59);
        $muted = imagecolorallocate($img, 100, 116, 139);
        $blue = imagecolorallocate($img, 0, 102, 179);
        imagefilledrectangle($img, 0, 0, 720, 140, $blue);
        imagettftext($img, 30, 0, 50, 85, imagecolorallocate($img, 255, 255, 255), self::font('Bold'), 'Transfer Berhasil');
        $rows = [['Tanggal', $date], ['Dari', $name], ['Ke', 'PD AKP2I Jawa Barat'], ['Bank tujuan', 'Bank Contoh Syariah'],
            ['No. referensi', strtoupper(substr(md5($name . $date . $amount), 0, 12))]];
        $y = 230;
        foreach ($rows as [$k, $v]) {
            imagettftext($img, 18, 0, 50, $y, $muted, self::font('Regular'), $k);
            imagettftext($img, 22, 0, 50, $y + 36, $dark, self::font('SemiBold'), $v);
            $y += 100;
        }
        imagettftext($img, 20, 0, 50, $y + 30, $muted, self::font('Regular'), 'Nominal');
        imagettftext($img, 40, 0, 50, $y + 90, $dark, self::font('Bold'), 'Rp' . number_format($amount, 0, ',', '.'));

        return self::save($img, $dir, 'png', 'local');
    }

    private static function cover(string $file, int $w, int $h)
    {
        $src = imagecreatefromjpeg($file);
        [$sw, $sh] = [imagesx($src), imagesy($src)];
        $scale = max($w / $sw, $h / $sh);
        [$cw, $ch] = [(int) ($w / $scale), (int) ($h / $scale)];
        $dst = imagecreatetruecolor($w, $h);
        imagecopyresampled($dst, $src, 0, 0, (int) (($sw - $cw) / 2), (int) (($sh - $ch) / 2), $w, $h, $cw, $ch);

        return $dst;
    }

    private static function wrap(string $text, string $font, int $size, int $max): array
    {
        $lines = [''];
        foreach (explode(' ', $text) as $word) {
            $try = trim(end($lines) . ' ' . $word);
            $box = imagettfbbox($size, 0, $font, $try);
            if ($box[2] - $box[0] > $max && end($lines) !== '') {
                $lines[] = $word;
            } else {
                $lines[count($lines) - 1] = $try;
            }
        }

        return $lines;
    }

    private static function font(string $weight): string
    {
        return realpath(self::ASSETS . "/fonts/PlusJakartaSans-{$weight}.ttf");
    }

    private static function save($img, string $dir, string $ext = 'jpg', string $disk = 'public'): string
    {
        ob_start();
        $ext === 'png' ? imagepng($img, null, 6) : imagejpeg($img, null, 82);
        $path = "{$dir}/" . bin2hex(random_bytes(10)) . ".{$ext}";
        Storage::disk($disk)->put($path, ob_get_clean());

        return $path;
    }
}
