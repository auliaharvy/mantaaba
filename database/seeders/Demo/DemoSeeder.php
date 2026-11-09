<?php

namespace Database\Seeders\Demo;

use App\Models\Setting;
use Database\Seeders\RolePermissionSeeder;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * Data DEMO realistis untuk presentasi (docs/DEMO.md). Jalankan di database kosong:
 *   php artisan migrate:fresh && php artisan db:seed --class=Database\\Seeders\\Demo\\DemoSeeder
 * Tidak dipanggil DatabaseSeeder dan TIDAK untuk produksi: semua orang, alamat, dan nomor fiktif.
 */
class DemoSeeder extends Seeder
{
    /** Matikan pembuatan PDF sertifikat (dipakai test agar cepat). */
    public static bool $withCertificatePdfs = true;

    public static Generator $faker;

    public function run(): void
    {
        mt_srand(2026);
        self::$faker = FakerFactory::create('id_ID');
        self::$faker->seed(2026);
        Notification::fake(); // tidak ada email terkirim saat seeding
        config(['queue.default' => 'sync']); // sertifikat langsung dibuat

        $this->call(RolePermissionSeeder::class);
        $this->settings();
        $this->call([PeopleSeeder::class, ContentSeeder::class, EventSeeder::class, MediaSeeder::class]);
        Cache::flush();
    }

    private function settings(): void
    {
        Setting::current()->update([
            'site_name' => 'PD AKP2I Jawa Barat',
            'tagline' => 'Pengurus Daerah Asosiasi Konsultan Pajak Publik Indonesia Provinsi Jawa Barat',
            'email' => 'sekretariat@akp2i-jabar.example.com',
            'phone' => '(022) 8888-1234',
            'whatsapp' => '0812-2000-4321',
            'address' => 'Ruko Graha Wiyata Blok B-7, Jl. Soekarno-Hatta, Kota Bandung, Jawa Barat 40286',
            'maps_embed' => 'https://maps.google.com/maps?q=Gedung+Sate+Bandung&z=15&output=embed',
            'facebook' => 'https://www.facebook.com/example',
            'instagram' => 'https://www.instagram.com/example',
            'youtube' => 'https://www.youtube.com/@example',
            'url_ejournal' => 'https://ejournal.example.org',
            'bank_account_info' => "Bank Contoh Syariah\nNo. Rekening 700-123-4567 (contoh)\na.n. PD AKP2I Jawa Barat\nCantumkan nama & judul acara pada berita transfer.",
            'dues_amounts' => ['utama' => 1200000, 'madya' => 900000, 'pratama' => 600000, 'kehormatan' => 0],
        ]);
    }

    /** Pilih acak (deterministik) satu elemen. */
    public static function pick(array $items)
    {
        return $items[mt_rand(0, count($items) - 1)];
    }

    public static function chance(int $percent): bool
    {
        return mt_rand(1, 100) <= $percent;
    }
}
