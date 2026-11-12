<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Halaman awal dari Grand Design (PRD 13.2), disusun sebagai blok. Hanya dibuat bila belum ada;
 * isi selanjutnya dikelola lewat CMS. `home` & `kebijakan-privasi` adalah halaman sistem.
 */
class PageSeeder extends Seeder
{
    /** Foto asli disalin SiteMediaSeeder dari database/seeders/media/mantaaba ke disk public. */
    private const IMG = 'pages/blocks/';

    public function run(): void
    {
        foreach ($this->pages() as $slug => $page) {
            $model = Page::firstOrNew(['slug' => $slug]);
            if (! $model->exists) {
                $model->forceFill($page + ['is_published' => true, 'is_system' => in_array($slug, ['home', 'kebijakan-privasi'], true)])->save();
            }
        }
    }

    private function pages(): array
    {
        $daftar = ['button1_text' => 'Daftar', 'button1_url' => '/pendaftaran'];

        return [
            'home' => [
                'title' => 'Beranda',
                'excerpt' => 'Wadah pembinaan berbasis realitas kehidupan: akselerasi karakter & vokasi bisnis pemuda menuju masyarakat Indonesia yang transparan pajak.',
                'blocks' => [
                    $this->block('hero', ['title' => 'Wadah Pembinaan Berbasis Realitas Kehidupan',
                        'subtitle' => 'Program akselerasi karakter & vokasi bisnis terintegrasi selama 6 bulan bagi pemuda lulusan SMA/sederajat — berasrama dan gratis, menuju masyarakat Indonesia yang transparan pajak.',
                        'overlay' => 55, 'image' => self::IMG . 'bangunan_mantaaba_2.webp', 'button2_text' => 'Kenali Kami', 'button2_url' => '/tentang'] + $daftar),
                    $this->block('stats', ['items' => [
                        ['value' => '6', 'label' => 'Bulan inkubasi'],
                        ['value' => '5', 'label' => 'Skema kompetensi'],
                        ['value' => '5–6', 'label' => 'Siswa per angkatan'],
                        ['value' => '100%', 'label' => 'Gratis: asrama, makan, kesehatan'],
                    ]]),
                    $this->block('image_text', ['image' => self::IMG . 'kbm_1.jpg', 'position' => 'left', 'title' => 'Selamat Datang', 'body' => '<p>Rumah Mantaaba Sigaling hadir sebagai <strong>Wadah Pembinaan Berbasis Realitas Kehidupan</strong> sekaligus <strong>Program Akselerasi Karakter &amp; Vokasi Bisnis Terintegrasi</strong>. Peserta didik tidak dihadapkan pada simulasi fiktif, melainkan langsung menjalankan ekosistem kehidupan yang mandiri: asrama, kebun, kolam ikan, dan dapur yang dikelola layaknya sebuah perusahaan.</p><p>Kami membina pemuda menjadi <strong>pemimpin peradaban</strong>: hatinya terpaut pada Sang Pencipta, pemikirannya merancang sistem yang mutakhir, dan tangannya tak henti menebar kebermanfaatan.</p>']),
                    $this->block('cards', ['title' => 'Keseharian di Rumah Mantaaba', 'intro' => 'Belajar, beribadah, bekerja, dan berbagi dalam satu ekosistem kehidupan.', 'columns' => '4', 'items' => [
                        ['image' => self::IMG . 'kbm_3.webp', 'title' => 'Belajar', 'text' => 'Kelas skema kompetensi: komunikasi bisnis, sistem informasi, akuntansi, perpajakan, dan teknologi.'],
                        ['image' => self::IMG . 'sholat_1.webp', 'title' => 'Beribadah', 'text' => 'Sholat berjamaah dan tadarus di tajug menjadi poros keseharian.'],
                        ['image' => self::IMG . 'panen_ikan_3.webp', 'title' => 'Panen bersama', 'text' => 'Kolam dan kebun dikelola siswa dari tebar benih hingga panen.'],
                        ['image' => self::IMG . 'barista_1.webp', 'title' => 'Berwirausaha', 'text' => 'Meracik kopi dan melayani tamu sebagai latihan unit usaha nyata.'],
                    ]]),
                    $this->block('skemas', ['title' => 'Lima Skema Kompetensi', 'intro' => 'Keterampilan teknis berbasis SKKNI, disusun progresif selama 6 bulan.']),
                    $this->block('units', ['title' => 'Laboratorium Kehidupan', 'intro' => 'Asrama, kebun, kolam ikan, dan dapur adalah arena problem-solving tempat siswa mempraktikkan manajemen, akuntansi, dan komunikasi setiap hari.']),
                    $this->block('latest', ['title' => 'Kabar Terbaru', 'source' => 'berita', 'count' => 3]),
                    $this->block('latest', ['title' => 'Agenda Mendatang', 'source' => 'agenda', 'count' => 3]),
                    $this->block('gallery', ['title' => 'Galeri Kegiatan', 'limit' => 6]),
                    $this->block('quote', ['arabic' => 'فَاسْتَبِقُوا الْخَيْرَاتِ', 'translation' => 'Maka berlomba-lombalah kamu dalam kebaikan.', 'source' => 'QS. Al-Baqarah: 148']),
                    $this->block('cta', ['title' => 'Program Pendidikan Gratis untuk Pemuda', 'text' => 'Inkubasi karakter & vokasi bisnis 6 bulan, berasrama, seluruh biaya ditanggung. Penerimaan dibuka per angkatan.',
                        'style' => 'primer', 'button2_text' => 'Dukung lewat Donasi', 'button2_url' => '/donasi'] + $daftar),
                ],
            ],
            'tentang' => [
                'title' => 'Tentang Kami',
                'excerpt' => 'Wadah pembinaan berbasis realitas kehidupan sekaligus program akselerasi karakter & vokasi bisnis terintegrasi.',
                'blocks' => [
                    $this->block('image_text', ['image' => self::IMG . 'tadarus_1.webp', 'position' => 'right', 'title' => 'Rumah Mantaaba Sigaling', 'body' => <<<'HTML'
<p>Rumah Mantaaba Sigaling hadir sebagai antitesis dari model pendidikan konvensional yang sering kali memisahkan teori dari realitas. Peserta didik tidak dihadapkan pada simulasi fiktif atau studi kasus di atas kertas, melainkan langsung menjalankan sebuah ekosistem kehidupan yang mandiri: mengelola dinamika asrama, memastikan ketahanan pangan melalui kebun dan kolam ikan, mengatur anggaran dan gizi di dapur, hingga mengorkestrasi seluruhnya layaknya roda operasional sebuah perusahaan.</p>
<p>Program ini memadukan <strong>standar kompetensi teknis profesional berbasis SKKNI</strong> — Komunikasi Bisnis, Sistem Informasi Manajemen, Akuntansi yang <em>audit-ready</em>, Perpajakan yang taat hukum, hingga ERP dan kecerdasan buatan — dengan <strong>cara pandang hidup Islami (<em>Tashawwur Islami</em>)</strong>.</p>
<p>Ilmu bisnis di sini diasah bukan untuk kompetisi yang mematikan pesaing, melainkan sebagai instrumen untuk mengangkat derajat sesama, memberdayakan kaum yang lemah, dan membangun ketahanan ekonomi kolektif demi terwujudnya masyarakat Indonesia yang transparan pajak.</p>
HTML]),
                    $this->block('cards', ['title' => 'Dua Fitrah Penciptaan', 'columns' => '2', 'items' => [
                        ['icon' => 'heart', 'title' => "'Abdullah", 'text' => 'Hamba yang menjadikan setiap nafas bisnis, kedisiplinan, dan keringatnya sebagai ibadah serta ketundukan kepada Allah.'],
                        ['icon' => 'sun', 'title' => 'Khalifatullah', 'text' => 'Wakil Allah yang mengemban mandat mengelola peradaban, memakmurkan bumi, dan merawat alam dengan penuh integritas.'],
                    ]]),
                    $this->block('cards', ['title' => 'Landasan Filosofis', 'intro' => 'Dua pilar nilai yang menjadi "DNA" pembentuk karakter: satu merekatkan ikatan sosial, satu mendorong kemajuan.', 'columns' => '2', 'items' => [
                        ['icon' => 'users', 'title' => "'Asabiyah", 'text' => 'Solidaritas prososial, ukhuwah, dan ikatan kolektif. "Keberhasilan saya tidak bermakna jika saudara saya gagal atau tertinggal" — no one left behind.'],
                        ['icon' => 'sparkles', 'title' => 'Fastabiqul Khairat', 'text' => 'Agilitas, inisiatif, dan determinasi berlomba menjadi pelopor kebaikan: bertindak sebelum disuruh, memperbaiki sebelum rusak.'],
                    ]]),
                    $this->block('quote', ['translation' => 'Kita melesat secepat dan sekuat mungkin untuk menjadi yang terbaik, dengan memastikan langkah ini kita tempuh bersama-sama tanpa ada satu pun saudara yang tertinggal.', 'source' => "Sinergi 'Asabiyah & Fastabiqul Khairat"]),
                    $this->block('cta', ['title' => 'Mari Bergabung', 'text' => 'Jadilah bagian dari ekosistem yang melahirkan pengusaha dan teknokrat Muslim yang bermartabat.', 'style' => 'krem', 'button2_text' => 'Lihat Program', 'button2_url' => '/program'] + $daftar),
                ],
            ],
            'program' => [
                'title' => 'Program Pembinaan',
                'excerpt' => 'Peta jalan pembinaan kehidupan yang utuh: inkubasi 6 bulan, magang, bekerja, kuliah hingga S1, dan menjadi wirausahawan.',
                'blocks' => [
                    $this->block('cards', ['title' => 'Fase I: Inkubasi 6 Bulan', 'intro' => 'Tiga pilar yang saling terhubung dan tidak dapat dipisahkan.', 'columns' => '3', 'items' => [
                        ['icon' => 'heart', 'title' => 'Fondasi Paradigma & Spiritual', 'text' => "Tashawwur Islami: kesadaran sebagai 'Abdullah dan Khalifatullah, sistem imun moral agar terhindar dari praktik bisnis yang culas."],
                        ['icon' => 'academic-cap', 'title' => 'Kompetensi Teknis & Vokasi', 'text' => 'Lima skema SKKNI: Fundamental, SIM, SIA, SIP, dan Teknologi Bisnis.'],
                        ['icon' => 'home', 'title' => 'Laboratorium Kehidupan', 'text' => 'Asrama, kebun, kolam ikan, dan dapur sebagai arena eksekusi nyata teori manajemen, akuntansi, dan komunikasi.'],
                    ]]),
                    $this->block('timeline', ['title' => 'Roadmap Pembinaan', 'intro' => 'Pendidikan tidak dirancang sebagai kursus singkat, melainkan pembinaan jangka panjang.', 'items' => [
                        ['time' => 'Bulan 1', 'title' => 'Skema Fundamental', 'text' => 'Komunikasi bisnis yang aktif & taktis serta logika matematika bisnis dasar.'],
                        ['time' => 'Bulan 2–3', 'title' => 'Sistem Informasi Manajemen', 'text' => 'Visi-misi, struktur organisasi, tupoksi, SOP, workflow, tertib administrasi, dan legalitas.'],
                        ['time' => 'Bulan 4', 'title' => 'Sistem Informasi Akuntansi', 'text' => 'COA, dokumen transaksi, vouching, jurnal, buku besar, hingga laporan keuangan yang audit-ready.'],
                        ['time' => 'Bulan 5', 'title' => 'Sistem Informasi Perpajakan', 'text' => 'Pemetaan akun ke SPT, rekonsiliasi, ekualisasi PPh Badan & PPN.'],
                        ['time' => 'Bulan 6', 'title' => 'Teknologi Bisnis', 'text' => 'ERP untuk sentralisasi data dan optimalisasi AI (prompt engineering).'],
                        ['time' => 'Bulan 7–12', 'title' => 'Magang & Pengenalan Bisnis Riil', 'text' => 'Adaptasi dunia kerja sebagai staf operasional/admin di unit bisnis afiliasi.'],
                        ['time' => 'Bulan 13 – lulus S1', 'title' => 'Bekerja pada Bisnis Riil & Kuliah', 'text' => 'Rotasi peran dari staf hingga direktur operasional, sambil kuliah jarak jauh (Universitas Terbuka) sampai S1.'],
                        ['time' => 'Tahun ke-9', 'title' => 'Ownership & Entrepreneurship', 'text' => 'Menjadi pendiri sekaligus pemilik bisnis mandiri.'],
                        ['time' => 'Selamanya', 'title' => 'Sedulur Selawase', 'text' => 'Ukhuwah yang terjaga lewat pertemuan rutin minimal tiap 3 bulan.'],
                    ]]),
                    $this->block('cards', ['title' => 'Penilaian 4 Dimensi', 'intro' => 'Kelulusan tidak sekadar diukur dari angka di atas kertas.', 'columns' => '4', 'items' => [
                        ['icon' => 'book-open', 'title' => 'Knowledge · 20%', 'text' => 'Daya tangkap atas teori manajerial, vokasi bisnis, dan literasi keagamaan.'],
                        ['icon' => 'chart-bar', 'title' => 'Skill · 25%', 'text' => 'Kecakapan teknis mengeksekusi tugas operasional di lapangan.'],
                        ['icon' => 'shield', 'title' => 'Character · 30%', 'text' => 'Kedisiplinan, kejujuran, adab kepada guru dan sesama, serta tanggung jawab.'],
                        ['icon' => 'users', 'title' => 'Social Cohesion · 25%', 'text' => 'Kontribusi membangun tim, gotong royong, dan mengangkat kapasitas rekan.'],
                    ]]),
                    $this->block('cards', ['title' => 'Profil Lulusan', 'intro' => 'Pengusaha dan Teknokrat Muslim yang utuh.', 'columns' => '2', 'items' => [
                        ['icon' => 'shield', 'title' => 'Bermartabat & Bertanggung Jawab', 'text' => 'Pemimpin bertauhid lurus berintegritas tinggi, jujur dalam setiap transaksi, taat asas legalitas, dan mengutamakan kemaslahatan masyarakat.'],
                        ['icon' => 'check', 'title' => 'Mandiri & Kompeten', 'text' => 'Fasih mengeksekusi operasional manajerial, mahir akuntansi dan perpajakan terstandar, serta adaptif memanfaatkan AI dan ERP.'],
                    ]]),
                    $this->block('faq', ['title' => 'Tanya Jawab', 'items' => [
                        ['question' => 'Apakah ada biaya?', 'answer' => 'Tidak. Seluruh pembelajaran, asrama, jaminan kesehatan, dan konsumsi harian ditanggung ekosistem Rumah Mantaaba Sigaling.'],
                        ['question' => 'Kapan pendaftaran dibuka?', 'answer' => 'Penerimaan dilakukan setiap 6 bulan (per angkatan) dengan kapasitas 5–6 siswa agar pembinaan tetap intensif.'],
                        ['question' => 'Siapa yang bisa mendaftar?', 'answer' => 'Pemuda usia produktif lulusan SMA/sederajat (atau seusia), bersedia tinggal di asrama dengan persetujuan orang tua, dan menandatangani pakta integritas. Prioritas bagi pemuda sekitar, keluarga kurang mampu, dan anak yatim.'],
                        ['question' => 'Bagaimana kelulusan ditentukan?', 'answer' => 'Melalui penilaian 4 dimensi (Knowledge, Skill, Character, Social Cohesion) pada setiap skema. Status alumni paripurna diberikan setelah menyelesaikan studi S1.'],
                    ]]),
                    $this->block('cta', ['title' => 'Siap Ditempa?', 'text' => 'Daftarkan diri pada angkatan berikutnya.', 'style' => 'aksen', 'button2_text' => 'Tanya Pengurus', 'button2_url' => '/kontak'] + $daftar),
                ],
            ],
            'kebijakan-privasi' => [
                'title' => 'Kebijakan Privasi',
                'template' => 'rich',
                'show_in_sitemap' => true,
                'body' => <<<'HTML'
<p>Rumah Mantaaba Sigaling menghormati privasi setiap pengunjung, calon peserta, anggota, dan donatur. Data pribadi (seperti NIK, data keluarga, kondisi ekonomi, dan riwayat kesehatan) hanya dipakai untuk keperluan seleksi dan pembinaan, disimpan secara tertutup, dan hanya dapat diakses oleh pengurus yang berwenang.</p>
<p>Nama dan foto anggota hanya ditampilkan di situs dengan persetujuan yang bersangkutan. Nama donatur tidak pernah ditampilkan untuk umum.</p>
<p>Pertanyaan atau permintaan penghapusan data dapat disampaikan melalui halaman Kontak.</p>
HTML,
            ],
        ];
    }

    private function block(string $type, array $data): array
    {
        return ['type' => $type, 'hidden' => false, 'data' => $data];
    }
}
