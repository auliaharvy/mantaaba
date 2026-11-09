<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/** Halaman awal dari Grand Design (PRD 13.2). Hanya dibuat bila belum ada; isi selanjutnya dikelola lewat CMS. */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $slug => $page) {
            Page::firstOrCreate(['slug' => $slug], $page + ['is_published' => true]);
        }
    }

    private function pages(): array
    {
        return [
            'profil' => [
                'title' => 'Profil Rumah Mantaaba Sigaling',
                'excerpt' => 'Wadah pembinaan berbasis realitas kehidupan sekaligus program akselerasi karakter & vokasi bisnis terintegrasi.',
                'body' => <<<'HTML'
<p>Rumah Mantaaba Sigaling hadir sebagai <strong>Wadah Pembinaan Berbasis Realitas Kehidupan</strong> sekaligus <strong>Program Akselerasi Karakter &amp; Vokasi Bisnis Terintegrasi</strong> bagi pemuda lulusan SMA dan sederajat. Peserta didik tidak dihadapkan pada simulasi fiktif, melainkan langsung menjalankan ekosistem kehidupan yang mandiri: asrama, kebun, kolam ikan, dan dapur yang dikelola layaknya sebuah perusahaan.</p>
<h2>Dua kekuatan yang dipadukan</h2>
<p>Program ini memadukan <strong>standar kompetensi teknis profesional berbasis SKKNI</strong> (Komunikasi Bisnis, Sistem Informasi Manajemen, Akuntansi, Perpajakan, serta ERP dan kecerdasan buatan) dengan <strong>cara pandang hidup Islami (<em>Tashawwur Islami</em>)</strong>: setiap peserta disadarkan akan perannya sebagai <em>'Abdullah</em> sekaligus <em>Khalifatullah</em>.</p>
<h2>Landasan filosofis</h2>
<ul>
<li><strong>'Asabiyah</strong> — solidaritas prososial dan ukhuwah: <em>no one left behind</em>.</li>
<li><strong>Fastabiqul Khairat</strong> — agilitas, inisiatif, dan berlomba menjadi pelopor kebaikan.</li>
</ul>
<p>Tujuan puncaknya adalah melahirkan pengusaha dan teknokrat Muslim yang bermartabat, mandiri, kompeten, dan tangguh, demi terwujudnya masyarakat Indonesia yang transparan pajak.</p>
HTML,
            ],
            'sambutan' => [
                'title' => 'Selamat Datang di Rumah Mantaaba Sigaling',
                'excerpt' => 'Kami membina pemuda menjadi pemimpin peradaban: hatinya terpaut pada Sang Pencipta, pemikirannya merancang sistem yang mutakhir, dan tangannya tak henti menebar kebermanfaatan.',
                'body' => <<<'HTML'
<p>Pendidikan di Rumah Mantaaba Sigaling tidak dirancang sebagai kursus singkat, melainkan peta jalan pembinaan kehidupan yang utuh: inkubasi karakter dan vokasi bisnis selama 6 bulan, dilanjutkan magang, bekerja di unit bisnis, kuliah hingga S1, dan akhirnya menjadi wirausahawan mandiri.</p>
<p>Seluruh fasilitas pembelajaran, asrama, kesehatan, dan konsumsi ditanggung oleh ekosistem Rumah Mantaaba Sigaling.</p>
HTML,
            ],
            'kebijakan-privasi' => [
                'title' => 'Kebijakan Privasi',
                'body' => <<<'HTML'
<p>Rumah Mantaaba Sigaling menghormati privasi setiap pengunjung, calon peserta, anggota, dan donatur. Data pribadi (seperti NIK, data keluarga, kondisi ekonomi, dan riwayat kesehatan) hanya dipakai untuk keperluan seleksi dan pembinaan, disimpan secara tertutup, dan hanya dapat diakses oleh pengurus yang berwenang.</p>
<p>Nama dan foto anggota hanya ditampilkan di situs dengan persetujuan yang bersangkutan. Nama donatur tidak pernah ditampilkan untuk umum.</p>
<p>Pertanyaan atau permintaan penghapusan data dapat disampaikan melalui halaman Kontak.</p>
HTML,
            ],
        ];
    }
}
