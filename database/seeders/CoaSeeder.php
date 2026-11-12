<?php

namespace Database\Seeders;

use App\Models\CoaAccount;
use Illuminate\Database\Seeder;

/**
 * COA Rumah Mantaaba Sigaling (berlaku sejak 1 Januari 2026, dari spreadsheet pengurus) + 6 akun tambahan sistem
 * (persediaan untuk modul stok, beban penyusutan untuk modul aset). Hanya bila COA masih kosong; selanjutnya diubah lewat dashboard.
 * Kelompok: 1 Aktiva · 2 Utang · 3 Modal · 4 Penerimaan utama · 5 Biaya langsung · 6 Beban pelaksanaan · 7 Pendapatan lain · 8 Biaya lain.
 */
class CoaSeeder extends Seeder
{
    /** [kode, nama, posisi Dr/Cr, akun kelompok (header)] */
    private const ACCOUNTS = [
            ['10000000', 'Aktiva', 'Dr', true],
            ['11000000', 'Aktiva Lancar', 'Dr', true],
            ['11100000', 'Kas', 'Dr', true],
            ['11110000', 'Kas Asrama', 'Dr', false],
            ['11120000', 'Kas Titipan Sementara', 'Dr', false],
            ['11130000', 'Kas Project', 'Dr', false],
            ['11200000', 'Bank', 'Dr', true],
            ['11210000', 'Bank BRI 8503', 'Dr', false],
            ['11220000', 'Bank Mandiri 5739', 'Dr', false],
            ['11230000', 'Bank C 0003', 'Dr', false],
            ['11300000', 'Deposito dan Surat Berharga Lainnya', 'Dr', false],
            ['11400000', 'Piutang Relasi', 'Dr', false],
            ['11500000', 'Piutang Anggota', 'Dr', false],
            ['11600000', 'Piutang Pajak', 'Dr', true],
            ['11610000', 'Piutang PPN', 'Dr', false],
            ['11620000', 'Piutang PPh 23', 'Dr', false],
            ['11700000', 'Uang Muka Kegiatan', 'Dr', false],
            ['11800000', 'Persediaan', 'Dr', true], // tambahan sistem
            ['11810000', 'Persediaan Bahan Pangan', 'Dr', false], // tambahan sistem
            ['11820000', 'Persediaan Sarana Produksi Kebun & Kolam', 'Dr', false], // tambahan sistem
            ['11830000', 'Persediaan Hasil Panen', 'Dr', false], // tambahan sistem
            ['11840000', 'Persediaan Barang Habis Pakai', 'Dr', false], // tambahan sistem
            ['11900000', 'Uang Muka Lainnya', 'Dr', false],
            ['12000000', 'Aktiva Tetap', 'Dr', true],
            ['12100000', 'Peralatan dan Perlengkapan', 'Dr', true],
            ['12110000', 'Peralatan dan Perlengkapan Pertukangan', 'Dr', false],
            ['12111000', 'Akumulasi Penyusutan Peralatan dan Perlengkapan Pertukangan', 'Cr', false],
            ['12120000', 'Peralatan dan Perlengkapan Perkebunan', 'Dr', false],
            ['12121000', 'Akumulasi Penyusutan Peralatan dan Perlengkapan Perkebunan', 'Cr', false],
            ['12130000', 'Peralatan dan Perlengkapan Perternakan dan Perikanan', 'Dr', false],
            ['12131000', 'Akumulasi Penyusutan Peralatan dan Perlengkapan Pertenakan dan Perikanan', 'Cr', false],
            ['12140000', 'Peralatan dan Perlengkapan Perkantoran', 'Dr', false],
            ['12141000', 'Akumulasi Penyusutan Peralatan dan Perlengkapan Perkantoran', 'Cr', false],
            ['12150000', 'Peralatan dan Perlengkapan KBM', 'Dr', false],
            ['12151000', 'Akumulasi Penyusutan Peralatan dan Perlengkapan KBM', 'Cr', false],
            ['12160000', 'Peralatan dan Perlengkapan Tajug dan Asrama', 'Dr', false],
            ['12161000', 'Akumulasi Penyusutan Peralatan dan Perlengkapan Tajug dan Asrama', 'Cr', false],
            ['12170000', 'Peralatan dan Perlengkapan Dapur dan Perkopian', 'Dr', false],
            ['12171000', 'Akumulasi Penyusutan Peralatan dan Perlengkapan Dapur dan Perkopian', 'Cr', false],
            ['12190000', 'Peralatan dan Perlengkapan Lainnya', 'Dr', false],
            ['12191000', 'Akumulasi Penyusutan Peralatan dan Perlengkapan Lainnya', 'Cr', false],
            ['12200000', 'Kendaraan', 'Dr', false],
            ['12210000', 'Akumulasi Penyusutan Kendaraan', 'Cr', false],
            ['12300000', 'Bangunan', 'Dr', false],
            ['12310000', 'Akumulasi Penyusutan Bangunan', 'Cr', false],
            ['12400000', 'Tanah', 'Dr', false],
            ['13000000', 'Aktiva Tidak Berwujud', 'Dr', true],
            ['13100000', 'Biaya Praoperasi', 'Dr', false],
            ['13110000', 'Akumulasi Amortisasi Biaya Praoperasi', 'Cr', false],
            ['13200000', 'Hak Atas Kekayaan Intelektual', 'Dr', false],
            ['13210000', 'Akumulasi Amortisasi Hak Atas Kekayaan Intelektual', 'Cr', false],
            ['13900000', 'Aktiva Tidak Berwujud Lainnya', 'Dr', false],
            ['13910000', 'Akumulasi Amortisasi Aktiva Tidak Berwujud Lainnya', 'Cr', false],
            ['20000000', 'Utang', 'Cr', true],
            ['21000000', 'Utang Lancar', 'Cr', true],
            ['21100000', 'Utang Kegiatan Rutin', 'Cr', false],
            ['21200000', 'Utang Kegiatan Proyek', 'Cr', false],
            ['21300000', 'Utang Anggota Wajib', 'Cr', false],
            ['21310000', 'Utang Anggota Angpau Relasi', 'Cr', false],
            ['21400000', 'Utang Relasi', 'Cr', false],
            ['21500000', 'Utang Titipan Anggota', 'Cr', false],
            ['21600000', 'Utang Pajak', 'Cr', true],
            ['21610000', 'Utang PPN', 'Cr', false],
            ['21620000', 'Utang PPh 23', 'Cr', false],
            ['22000000', 'Utang Jangka Panjang', 'Cr', true],
            ['22100000', 'Utang Bank', 'Cr', false],
            ['22200000', 'Utang Sewa Guna Usaha', 'Cr', false],
            ['22300000', 'Utang Pendiri dan Pengurus', 'Cr', false],
            ['22900000', 'Utang Jangka Panjang Lainnya', 'Cr', false],
            ['30000000', 'Modal', 'Cr', true],
            ['31000000', 'Modal Disetor Tunai', 'Cr', false],
            ['32000000', 'Modal Dalam Bentuk Aktiva Tetap', 'Cr', false],
            ['33000000', 'Sisa Kelebihan (Kekurangan) Kegiatan Operasional Bersih Sampai Dengan Tahun Lalu', 'Cr', false],
            ['34000000', 'Sisa Kelebihan (Kekurangan) Kegiatan Operasional Bersih Tahun Berjalan', 'Cr', false],
            ['39000000', 'Pertumbuhan Modal Lainnya', 'Cr', false],
            ['40000000', 'Penerimaan dan Pendapatan Utama', 'Cr', true],
            ['41000000', 'Donasi Owner', 'Cr', false],
            ['42000000', 'Donasi Anggota Rumah Mantaaba', 'Cr', true],
            ['42100000', 'Donasi Anggota Untuk Rumah Tangga Asrama', 'Cr', false],
            ['42200000', 'Donasi Anggota Untuk Perawatan Asrama', 'Cr', false],
            ['42300000', 'Donasi Anggota Untuk Pengembangan Skill Anggota', 'Cr', false],
            ['42400000', 'Donasi Anggota Untuk Kaderisasi Adik Kelas', 'Cr', false],
            ['43000000', 'Donasi Pihak Ketiga Lainnya', 'Cr', false],
            ['49000000', 'Donasi dan Hibah Lainnya', 'Cr', false],
            ['50000000', 'Harga Pokok dan Biaya Langsung Terkait Penerimaan dan Pendapatan Utama', 'Dr', true],
            ['51000000', 'Project Owner', 'Dr', false],
            ['52000000', 'Biaya Anggota Rumah Mantaaba', 'Dr', true],
            ['52100000', 'Biaya Anggota Untuk Rumah Tangga Asrama', 'Dr', true],
            ['52110000', 'Biaya Anggota Untuk Konsumsi Anggota', 'Dr', false],
            ['52120000', 'Biaya Anggota Untuk Energy Dapur', 'Dr', false],
            ['52130000', 'Biaya Anggota Untuk Jamuan Tamu', 'Dr', false],
            ['52140000', 'Biaya Anggota Untuk Kebutuhan MCK', 'Dr', false],
            ['52190000', 'Biaya Anggota Untuk Kebutuhan Konsumsi Lainnya', 'Dr', false],
            ['52200000', 'Donasi Anggota Untuk Perawatan Asrama dan Kebutuhan Asrama', 'Dr', true],
            ['52210000', 'Biaya Anggota Untuk Transport Asrama', 'Dr', false],
            ['52220000', 'Biaya Anggota Untuk Kebersihan Asrama', 'Dr', false],
            ['52230000', 'Biaya Anggota Untuk Energy Asrama', 'Dr', false],
            ['52240000', 'Biaya Anggota Untuk Perawatan Kolam', 'Dr', false],
            ['52250000', 'Biaya Anggota Untuk Perawatan Kebun', 'Dr', false],
            ['52260000', 'Biaya Anggota Untuk Perawatan Kandang', 'Dr', false],
            ['52270000', 'Biaya Anggota Untuk Komunikasi Asrama', 'Dr', false],
            ['52290000', 'Biaya Anggota Untuk Perawatan Asrama Lainnya', 'Dr', false],
            ['52300000', 'Biaya Anggota Untuk Pengembangan Skill Anggota', 'Dr', true],
            ['52310000', 'Biaya ATK dan Periperal KBM', 'Dr', false],
            ['52320000', 'Biaya Honor Guru Pihak Ketiga', 'Dr', false],
            ['52330000', 'Biaya Sertifikasi Rumah Mantaaba (LPK)', 'Dr', false],
            ['52340000', 'Biaya Seragam Anggota Rumah Mantaaba', 'Dr', false],
            ['52390000', 'Biaya Anggota Untuk Pengembangan Skill Anggota Lainnya', 'Dr', false],
            ['52400000', 'Biaya Anggota Untuk Kaderisasi Adik Kelas', 'Dr', false],
            ['52500000', 'Biaya Pihak Ketiga Lainnya', 'Dr', false],
            ['52900000', 'Biaya dan Hibah Lainnya', 'Dr', false],
            ['60000000', 'Beban Pelaksanaan Kegiatan Rumah Mantaaba', 'Dr', true],
            ['61000000', 'Beban Tenaga Kerja Rumah Mantaaba', 'Dr', false],
            ['62000000', 'Beban Iuran Kebersihan dan Keamanan', 'Dr', false],
            ['63000000', 'Beban Perizinan dan Legalitas', 'Dr', false],
            ['64000000', 'Beban Sumbangan dan Donasi', 'Dr', false],
            ['65000000', 'Beban Humas', 'Dr', false],
            ['68000000', 'Beban Penyusutan dan Amortisasi', 'Dr', false], // tambahan sistem
            ['69000000', 'Beban Lainnya', 'Dr', false],
            ['70000000', 'Penerimaan dan Pendapatan Lainnya', 'Cr', true],
            ['79000000', 'Penerimaan dan Pendapatan Lain-Lain', 'Cr', false],
            ['80000000', 'Biaya Lainnya', 'Dr', true],
            ['81000000', 'Biaya Admin Bank', 'Dr', false],
            ['89000000', 'Biaya Lain-Lain', 'Dr', false],
    ];

    private const RECEIVABLE = ['11400000', '11500000'];

    private const PAYABLE = ['21100000', '21200000', '21300000', '21310000', '21400000', '21500000', '22100000', '22200000', '22300000', '22900000'];

    public function run(): void
    {
        if (CoaAccount::exists()) {
            return;
        }
        $ids = [];
        foreach (self::ACCOUNTS as $order => [$code, $name, $position, $header]) {
            $type = ['1' => 'aset', '2' => 'liabilitas', '3' => 'aset_neto', '4' => 'pendapatan', '5' => 'beban', '6' => 'beban', '7' => 'pendapatan', '8' => 'beban'][$code[0]];
            $normal = $position === 'Dr' ? 'debit' : 'kredit';
            // induk = akun kelompok terakhir yang kodenya (tanpa nol di belakang) menjadi awalan kode ini
            $parent = collect($ids)->filter(fn ($id, $c) => $c !== $code && str_starts_with($code, rtrim($c, '0')) && $this->isHeader($c))->keys()->last();
            $ids[$code] = CoaAccount::create([
                'code' => $code, 'name' => $name, 'type' => $type, 'normal_balance' => $normal, 'is_header' => $header, 'sort_order' => $order,
                'parent_id' => $parent ? $ids[$parent] : null,
                'is_contra' => $type === 'aset' && $normal === 'kredit',
                'is_cash' => str_starts_with($code, '111') && ! $header, 'is_bank' => str_starts_with($code, '112') && ! $header,
                'is_receivable' => in_array($code, self::RECEIVABLE, true), 'is_payable' => in_array($code, self::PAYABLE, true),
                'restriction' => in_array($type, ['aset_neto', 'pendapatan', 'beban'], true) ? 'tanpa_pembatasan' : null,
                'cash_flow_group' => match (true) { str_starts_with($code, '12'), str_starts_with($code, '13'), $code === '11300000' => 'investasi', str_starts_with($code, '22'), $code[0] === '3' => 'pendanaan', default => 'operasi' },
                'statement_group' => self::group($code, $normal),
            ])->id;
        }
    }

    private function isHeader(string $code): bool
    {
        return collect(self::ACCOUNTS)->firstWhere(0, $code)[3] ?? false;
    }

    /** Pos laporan (dipakai laporan keuangan, arus kas, rasio, transparansi). */
    private static function group(string $code, string $normal): string
    {
        return match (true) {
            str_starts_with($code, '111'), str_starts_with($code, '112') => 'Kas dan setara kas',
            str_starts_with($code, '113') => 'Deposito & surat berharga',
            str_starts_with($code, '114'), str_starts_with($code, '115') => 'Piutang',
            str_starts_with($code, '116') => 'Piutang pajak',
            str_starts_with($code, '118') => 'Persediaan',
            str_starts_with($code, '117'), str_starts_with($code, '119') => 'Uang muka',
            str_starts_with($code, '12') => $normal === 'kredit' ? 'Akumulasi penyusutan' : 'Aset tetap',
            str_starts_with($code, '13') => $normal === 'kredit' ? 'Akumulasi amortisasi' : 'Aset tidak berwujud',
            str_starts_with($code, '216') => 'Utang pajak',
            str_starts_with($code, '21') => 'Utang lancar',
            str_starts_with($code, '22') => 'Utang jangka panjang',
            $code[0] === '3' => 'Modal (aset neto)',
            $code[0] === '4' => 'Donasi',
            $code[0] === '5' => 'Beban program',
            $code[0] === '6' => 'Beban pelaksanaan kegiatan',
            $code[0] === '7' => 'Pendapatan lainnya',
            $code[0] === '8' => 'Biaya lainnya',
            default => 'Lainnya',
        };
    }
}
