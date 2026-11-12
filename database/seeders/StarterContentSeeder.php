<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Berita & agenda awal agar situs tidak kosong saat diluncurkan (dipakai bila MANTAABA_DEMO=false / produksi).
 * Isi bersifat contoh: tinjau dan sesuaikan lewat dashboard sebelum diumumkan. Tanggal relatif terhadap waktu seeding.
 * Hanya berjalan bila belum ada berita/agenda sama sekali.
 */
class StarterContentSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::role('super_admin')->value('id');
        $now = now()->startOfDay();

        if (! $author) {
            $this->command?->warn('Berita awal dilewati: belum ada Super Admin (isi MANTAABA_ADMIN_EMAIL).');
        } elseif (! Blog::withTrashed()->exists()) {
            foreach ($this->news() as $i => [$title, $category, $photo, $daysAgo, $excerpt, $body]) {
                Blog::create(['title' => $title, 'excerpt' => $excerpt, 'body' => $body, 'status' => 'published',
                    'thumbnail' => $this->photo($photo, 'blog'), 'published_at' => $now->copy()->subDays($daysAgo)->setTime(8, 0)->addMinutes($i * 7),
                    'category_id' => Category::where('name', $category)->value('id'), 'user_id' => $author]);
            }
        }

        if (! Event::exists()) {
            foreach ($this->events($now) as $event) {
                $event['thumbnail'] = $this->photo($event['thumbnail'], 'event');
                Event::create($event + ['mode' => 'offline', 'location' => 'Rumah Mantaaba Sigaling']);
            }
        }
    }

    /** Salinan foto per konten: mengganti gambar lewat CMS menghapus file lama, jadi foto galeri/halaman tidak boleh dipakai bersama. */
    private function photo(string $file, string $dir): string
    {
        $path = "{$dir}/{$file}";
        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, file_get_contents(database_path("seeders/media/mantaaba/{$file}")));
        }

        return $path;
    }

    private function news(): array
    {
        return [
            ['Pendaftaran Angkatan Baru Rumah Mantaaba Sigaling Dibuka', 'Pengumuman', 'bangunan_mantaaba_2.webp', 2,
                'Program pembinaan berasrama selama enam bulan, tanpa biaya, untuk pemuda lulusan SMA/sederajat. Pendaftaran dilakukan secara online.',
                '<p>Rumah Mantaaba Sigaling membuka pendaftaran angkatan baru program akselerasi karakter dan vokasi bisnis. Program berlangsung selama enam bulan di asrama, tanpa biaya, dan terbuka bagi pemuda lulusan SMA/sederajat yang siap belajar, bekerja, dan hidup bersama.</p>'
                . '<p>Selama program, siswa menempuh skema kompetensi bertahap, dimulai dari Fundamental (12 jurus) lalu dilanjutkan ke skema bidang seperti Sistem Informasi Manajemen. Pembelajaran di kelas dipadukan dengan praktik langsung di unit-unit Rumah Mantaaba: kebun, kolam ikan, dapur, dan administrasi.</p>'
                . '<h2>Tahapan seleksi</h2><ol><li>Mengisi formulir dan mengunggah berkas secara online</li><li>Verifikasi berkas</li><li>Wawancara</li><li>Kunjungan rumah</li><li>Pengumuman hasil</li></ol>'
                . '<p>Jadwal, kuota, dan persyaratan lengkap tercantum di halaman <a href="/pendaftaran">Pendaftaran</a>. Calon pendaftar dapat memeriksa status pendaftarannya kapan saja dengan nomor pendaftaran.</p>'],

            ['Panen Kolam Ikan, Hasilnya Langsung Mengisi Dapur Asrama', 'Kebun & Kolam', 'panen_ikan_2.webp', 9,
                'Siswa dan pengurus memanen kolam ikan bersama. Sebagian hasil dimasak di dapur asrama, sebagian lagi dijual untuk menopang biaya operasional.',
                '<p>Pagi itu suasana di sekitar kolam ramai. Siswa turun langsung menjaring ikan, sementara yang lain menimbang dan mencatat hasil panen. Bagi siswa, panen bukan sekadar kegiatan seru: setiap kilogram dicatat sebagai bahan belajar menghitung biaya pakan, tingkat hidup ikan, dan harga pokok produksi.</p>'
                . '<p>Sebagian hasil panen langsung diserahkan ke dapur asrama untuk menu makan bersama. Sebagian lainnya dijual, dan hasilnya dicatat sebagai pendapatan unit kolam. Dengan begitu siswa melihat sendiri bagaimana satu siklus budidaya berjalan dari tebar benih sampai menjadi laporan keuangan.</p>'
                . '<p>Siklus berikutnya segera dimulai setelah kolam dibersihkan dan dikeringkan beberapa hari.</p>'],

            ['Mengenal 12 Jurus Fundamental, Bekal Pertama Setiap Siswa', 'Program', 'kbm_1.jpg', 16,
                'Sebelum masuk ke skema bidang, setiap siswa menempuh skema Fundamental: dua belas unit kompetensi dasar untuk bekerja dan hidup bermasyarakat.',
                '<p>Bulan pertama di Rumah Mantaaba diisi dengan skema Fundamental yang dikenal sebagai <strong>12 jurus</strong>. Setiap jurus adalah satu unit kompetensi dengan elemen dan kriteria unjuk kerja yang jelas, sehingga siswa tahu persis apa yang harus mereka kuasai.</p>'
                . '<p>Penilaian tidak hanya melihat pengetahuan. Ada empat dimensi yang diukur: pengetahuan, keterampilan, karakter, dan kekompakan sosial. Nilai karakter mendapat bobot terbesar, karena kejujuran dan tanggung jawab menjadi dasar semua keterampilan lainnya.</p>'
                . '<p>Daftar lengkap unit kompetensi dan modulnya dapat dilihat di halaman <a href="/kurikulum">Kurikulum</a>, termasuk dokumen pemetaan standar kompetensi yang bisa diunduh.</p>'],

            ['Tadarus Selepas Maghrib, Menjaga Ritme Ruhani di Asrama', 'Kabar Asrama', 'tadarus_1.webp', 23,
                'Di sela padatnya kelas dan praktik, tadarus bersama selepas maghrib menjadi waktu siswa menenangkan diri dan saling menyimak bacaan.',
                '<p>Setiap selesai sholat maghrib berjamaah, siswa duduk melingkar di tajug untuk tadarus. Mereka bergantian membaca dan saling menyimak, dengan pendampingan pengurus bagi yang masih belajar memperbaiki bacaan.</p>'
                . '<p>Kegiatan ini menjadi bagian dari pembinaan karakter yang berjalan sepanjang hari, bersama piket asrama, kajian, dan kerja di unit. Ritme yang teratur ini membantu siswa membangun kebiasaan baik yang diharapkan tetap terbawa setelah mereka lulus.</p>'],

            ['Belajar Meracik Kopi: Dari Biji Hingga ke Cangkir', 'Program', 'barista_1.webp', 31,
                'Kelas barista memperkenalkan siswa pada seluruh rantai usaha kopi, dari mengenal biji, menyeduh, hingga menghitung harga jual per cangkir.',
                '<p>Kelas barista menjadi salah satu yang paling dinanti. Siswa belajar mengenali jenis biji, mengatur gilingan, dan menyeduh dengan beberapa metode manual.</p>'
                . '<p>Yang membuat kelas ini berbeda adalah sisi bisnisnya. Setelah menyeduh, siswa diajak menghitung biaya bahan per cangkir, menentukan harga jual, dan mencatat penjualan. Keterampilan teknis dan pemahaman usaha dilatih bersamaan, sesuai tujuan program untuk melahirkan pemuda yang siap bekerja maupun berwirausaha.</p>'],

            ['Kebun di Bawah Naungan Pinus Mulai Menghasilkan', 'Kebun & Kolam', 'kebun_3.webp', 40,
                'Bedengan yang disiapkan siswa sejak awal musim mulai dipanen. Sayuran dari kebun kini rutin masuk ke dapur asrama.',
                '<p>Lahan di bawah naungan pohon pinus kini tertata menjadi bedengan-bedengan sayuran. Siswa yang bertugas di kebun mencatat setiap kegiatan harian, mulai dari penyiraman, pemupukan, hingga pengendalian hama, lengkap dengan foto.</p>'
                . '<p>Hasil panen pertama sudah masuk ke dapur asrama dan mengurangi belanja sayur. Catatan biaya dan hasil tiap petak dipakai sebagai bahan evaluasi: tanaman mana yang paling cocok dengan lahan dan mana yang perlu diganti di musim berikutnya.</p>'],

            ['Kajian Tajug: Jujur dalam Bermuamalah', 'Kajian', 'sholat_1.webp', 47,
                'Kajian rutin di tajug membahas kejujuran dalam jual beli dan pencatatan keuangan, nilai yang juga dipraktikkan siswa di unit usaha.',
                '<p>Kajian tajug kali ini mengangkat tema kejujuran dalam bermuamalah. Pembahasan berangkat dari adab jual beli dalam Islam, lalu dikaitkan dengan keseharian siswa: menimbang hasil panen dengan benar, mencatat setiap pengeluaran dengan bukti, dan tidak menyetujui pengeluaran sendiri.</p>'
                . '<p>Kajian tajug terbuka untuk umum dan digelar secara rutin. Jadwal kajian berikutnya dapat dilihat di halaman <a href="/agenda">Agenda</a>.</p>'],
        ];
    }

    private function events(Carbon $now): array
    {
        $at = fn (Carbon $day, string $time) => $day->copy()->setTimeFromTimeString($time);
        $kajian = $now->copy()->next(Carbon::FRIDAY)->addWeek();
        $openHouse = $now->copy()->next(Carbon::SATURDAY)->addWeeks(4);
        $sedulur = $now->copy()->next(Carbon::SUNDAY)->addWeeks(5);
        $barista = $now->copy()->next(Carbon::SUNDAY)->addWeeks(8);
        $panen = $now->copy()->previous(Carbon::SUNDAY)->subWeeks(3);
        $kajianLalu = $now->copy()->previous(Carbon::FRIDAY)->subWeek();

        return [
            ['title' => 'Kajian Tajug: Fiqih Muamalah untuk Pebisnis Muda', 'type' => 'kajian_tajug', 'audience' => 'publik', 'status' => 'open', 'quota' => 80,
                'thumbnail' => 'sholat_1.webp', 'starts_at' => $at($kajian, '19:30'), 'ends_at' => $at($kajian, '21:00'),
                'registration_closes_at' => $at($kajian, '12:00'),
                'body' => '<p>Kajian rutin ba\'da isya di tajug Rumah Mantaaba. Tema kali ini: prinsip akad, larangan riba dan gharar, serta adab berdagang yang relevan untuk pemuda yang ingin memulai usaha.</p><p>Terbuka untuk umum, putra dan putri. Silakan daftar agar panitia dapat menyiapkan tempat; tiket QR dikirim setelah mendaftar.</p>'],
            ['title' => 'Open House Rumah Mantaaba Sigaling', 'type' => 'open_house', 'audience' => 'publik', 'status' => 'open', 'quota' => 60,
                'thumbnail' => 'bangunan_mantaaba_1.webp', 'starts_at' => $at($openHouse, '09:00'), 'ends_at' => $at($openHouse, '12:00'),
                'registration_closes_at' => $at($openHouse->copy()->subDays(2), '23:59'),
                'body' => '<p>Kenali lebih dekat kehidupan di Rumah Mantaaba: berkeliling asrama, kelas, kebun, dan kolam, berbincang dengan siswa dan pengurus, serta mendapatkan penjelasan tentang program dan tahapan pendaftaran.</p><p>Cocok untuk calon siswa beserta orang tua/wali, sekolah, dan calon mitra. Kuota terbatas, mohon daftar terlebih dahulu.</p>'],
            ['title' => 'Majelis Sedulur Selawase', 'type' => 'sedulur_selawase', 'audience' => 'anggota', 'status' => 'open', 'quota' => 150,
                'thumbnail' => 'panen_ikan_1.webp', 'starts_at' => $at($sedulur, '08:00'), 'ends_at' => $at($sedulur, '11:00'),
                'body' => '<p>Pertemuan berkala keluarga besar Rumah Mantaaba: alumni, siswa, pengurus, dan relawan. Diisi tausiyah, kabar dari setiap angkatan, dan makan bersama.</p>'],
            ['title' => 'Pelatihan Barista Dasar untuk Umum', 'type' => 'kegiatan_umum', 'audience' => 'publik', 'status' => 'open', 'quota' => 20,
                'thumbnail' => 'barista_1.webp', 'starts_at' => $at($barista, '08:30'), 'ends_at' => $at($barista, '15:00'),
                'registration_closes_at' => $at($barista->copy()->subDays(3), '23:59'),
                'body' => '<p>Pelatihan satu hari tentang dasar-dasar kopi: mengenal biji, menggiling, menyeduh manual, hingga menghitung harga pokok per cangkir. Dipandu siswa dan mentor unit kopi Rumah Mantaaba.</p><p>Peserta terbatas 20 orang agar setiap peserta mendapat kesempatan praktik.</p>'],
            ['title' => 'Panen Bersama Kolam Ikan', 'type' => 'kegiatan_umum', 'audience' => 'publik', 'status' => 'done', 'quota' => 40,
                'thumbnail' => 'panen_ikan_3.webp', 'starts_at' => $at($panen, '07:00'), 'ends_at' => $at($panen, '10:00'),
                'body' => '<p>Panen kolam ikan bersama siswa dan warga sekitar, dilanjutkan makan bersama dari hasil panen.</p>'],
            ['title' => 'Kajian Tajug: Amanah dalam Bekerja', 'type' => 'kajian_tajug', 'audience' => 'publik', 'status' => 'done', 'quota' => 80,
                'thumbnail' => 'tadarus_1.webp', 'starts_at' => $at($kajianLalu, '19:30'), 'ends_at' => $at($kajianLalu, '21:00'),
                'body' => '<p>Kajian tentang makna amanah dalam pekerjaan sehari-hari, dari menjaga waktu hingga menjaga harta yang dititipkan.</p>'],
        ];
    }
}
