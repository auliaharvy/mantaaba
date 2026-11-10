<?php

namespace Database\Seeders;

use App\Models\CoaAccount;
use Illuminate\Database\Seeder;

/** Bagan akun ISAK 35 (PRD 13.3). Hanya bila COA masih kosong; selanjutnya diubah lewat dashboard. */
class CoaSeeder extends Seeder
{
    public function run(): void
    {
        if (CoaAccount::exists()) {
            return;
        }
        $order = 0;
        $add = function (string $code, string $name, string $type, ?string $parent, array $flags = []) use (&$order) {
            $normal = in_array($type, ['aset', 'beban'], true) ? 'debit' : 'kredit';
            if (! empty($flags['is_contra'])) {
                $normal = $normal === 'debit' ? 'kredit' : 'debit';
            }

            return CoaAccount::create(['code' => $code, 'name' => $name, 'type' => $type, 'normal_balance' => $normal, 'sort_order' => $order++,
                'parent_id' => $parent ? CoaAccount::where('code', $parent)->value('id') : null, 'cash_flow_group' => $flags['cash_flow_group'] ?? 'operasi'] + $flags);
        };
        $h = ['is_header' => true];

        $add('1', 'ASET', 'aset', null, $h);
        $add('1-1', 'Aset Lancar', 'aset', '1', $h + ['statement_group' => 'Aset Lancar']);
        $add('1-1100', 'Kas', 'aset', '1-1', $h);
        $add('1-1101', 'Kas Yayasan', 'aset', '1-1100', ['is_cash' => true, 'statement_group' => 'Kas dan setara kas']);
        $add('1-1102', 'Kas Kecil Asrama/Dapur', 'aset', '1-1100', ['is_cash' => true, 'statement_group' => 'Kas dan setara kas']);
        $add('1-1200', 'Bank', 'aset', '1-1', $h);
        $add('1-1201', 'Bank Operasional', 'aset', '1-1200', ['is_bank' => true, 'statement_group' => 'Kas dan setara kas']);
        $add('1-1202', 'Bank Donasi', 'aset', '1-1200', ['is_bank' => true, 'statement_group' => 'Kas dan setara kas']);
        $add('1-1300', 'Piutang', 'aset', '1-1', $h);
        $add('1-1301', 'Piutang Usaha', 'aset', '1-1300', ['is_receivable' => true, 'statement_group' => 'Piutang']);
        $add('1-1302', 'Piutang Lain-lain/Kasbon', 'aset', '1-1300', ['is_receivable' => true, 'statement_group' => 'Piutang']);
        $add('1-1400', 'Persediaan', 'aset', '1-1', $h);
        foreach (['1-1401' => 'Persediaan Bahan Pangan', '1-1402' => 'Persediaan Sarana Produksi Kebun & Kolam', '1-1403' => 'Persediaan Hasil Panen', '1-1404' => 'Persediaan Habis Pakai'] as $code => $name) {
            $add($code, $name, 'aset', '1-1400', ['statement_group' => 'Persediaan']);
        }
        $add('1-1500', 'Uang Muka & Dibayar Dimuka', 'aset', '1-1', ['statement_group' => 'Uang muka & dibayar dimuka']);
        $add('1-2', 'Aset Tidak Lancar', 'aset', '1', $h + ['cash_flow_group' => 'investasi']);
        foreach (['1-2100' => 'Tanah', '1-2200' => 'Bangunan', '1-2300' => 'Kendaraan', '1-2400' => 'Peralatan & Inventaris', '1-2500' => 'Elektronik'] as $code => $name) {
            $add($code, $name, 'aset', '1-2', ['cash_flow_group' => 'investasi', 'statement_group' => 'Aset tetap']);
        }
        $add('1-2900', 'Akumulasi Penyusutan', 'aset', '1-2', $h + ['is_contra' => true, 'cash_flow_group' => 'investasi']);
        foreach (['1-2902' => 'Akumulasi Penyusutan Bangunan', '1-2903' => 'Akumulasi Penyusutan Kendaraan', '1-2904' => 'Akumulasi Penyusutan Peralatan', '1-2905' => 'Akumulasi Penyusutan Elektronik'] as $code => $name) {
            $add($code, $name, 'aset', '1-2900', ['is_contra' => true, 'cash_flow_group' => 'investasi', 'statement_group' => 'Akumulasi penyusutan']);
        }

        $add('2', 'LIABILITAS', 'liabilitas', null, $h);
        $add('2-1100', 'Utang Usaha', 'liabilitas', '2', ['is_payable' => true, 'statement_group' => 'Utang']);
        $add('2-1200', 'Utang Lain-lain', 'liabilitas', '2', ['is_payable' => true, 'statement_group' => 'Utang']);
        $add('2-1300', 'Pendapatan Diterima Dimuka', 'liabilitas', '2', ['statement_group' => 'Pendapatan diterima dimuka']);
        $add('2-1400', 'Utang Pajak', 'liabilitas', '2', ['statement_group' => 'Utang pajak']);

        $add('3', 'ASET NETO', 'aset_neto', null, $h);
        $add('3-1000', 'Aset Neto Tanpa Pembatasan', 'aset_neto', '3', $h + ['restriction' => 'tanpa_pembatasan']);
        $add('3-1100', 'Saldo Aset Neto', 'aset_neto', '3-1000', ['restriction' => 'tanpa_pembatasan', 'statement_group' => 'Aset neto tanpa pembatasan']);
        $add('3-1200', 'Surplus/Defisit Tahun Berjalan', 'aset_neto', '3-1000', ['restriction' => 'tanpa_pembatasan', 'statement_group' => 'Aset neto tanpa pembatasan']);
        $add('3-2000', 'Aset Neto Dengan Pembatasan', 'aset_neto', '3', $h + ['restriction' => 'dengan_pembatasan']);
        foreach (['3-2100' => ['Dana Pendidikan', 'operasi'], '3-2200' => ['Dana Pangan', 'operasi'], '3-2300' => ['Dana Pembangunan', 'pendanaan'], '3-2400' => ['Wakaf', 'pendanaan']] as $code => [$name, $flow]) {
            $add($code, $name, 'aset_neto', '3-2000', ['restriction' => 'dengan_pembatasan', 'cash_flow_group' => $flow, 'statement_group' => 'Aset neto dengan pembatasan']);
        }

        $add('4', 'PENDAPATAN', 'pendapatan', null, $h);
        $tanpa = ['restriction' => 'tanpa_pembatasan'];
        $add('4-1100', 'Donasi Tanpa Pembatasan', 'pendapatan', '4', $tanpa + ['statement_group' => 'Donasi']);
        $add('4-1200', 'Donasi Dengan Pembatasan', 'pendapatan', '4', ['restriction' => 'dengan_pembatasan', 'statement_group' => 'Donasi']);
        $add('4-1300', 'Donasi Natura', 'pendapatan', '4', $tanpa + ['statement_group' => 'Donasi']);
        $add('4-1400', 'Hibah & Wakaf', 'pendapatan', '4', ['restriction' => 'dengan_pembatasan', 'statement_group' => 'Donasi']);
        $add('4-2100', 'Penjualan Hasil Kebun & Kolam', 'pendapatan', '4', $tanpa + ['statement_group' => 'Pendapatan usaha']);
        $add('4-2200', 'Kontribusi Unit Bisnis', 'pendapatan', '4', $tanpa + ['statement_group' => 'Pendapatan usaha']);
        $add('4-3100', 'Pendapatan Lain', 'pendapatan', '4', $tanpa + ['statement_group' => 'Pendapatan lain']);
        $add('4-9000', 'Aset Neto Dibebaskan dari Pembatasan', 'pendapatan', '4', $tanpa + ['statement_group' => 'Aset neto dibebaskan dari pembatasan']);

        $add('5', 'BEBAN', 'beban', null, $h);
        $add('5-1', 'Beban Program', 'beban', '5', $h + ['statement_group' => 'Beban program']);
        foreach (['5-1100' => 'Beban Pendidikan & Pelatihan', '5-1200' => 'Beban Konsumsi (Dapur)', '5-1300' => 'Beban Asrama (listrik, air, kebersihan)', '5-1400' => 'Beban Produksi Kebun & Kolam', '5-1500' => 'Beban Kesehatan Siswa', '5-1600' => 'Beban Kegiatan Keagamaan & Sedulur Selawase'] as $code => $name) {
            $add($code, $name, 'beban', '5-1', ['statement_group' => 'Beban program']);
        }
        $add('5-2', 'Beban Operasional', 'beban', '5', $h + ['statement_group' => 'Beban operasional']);
        foreach (['5-2100' => 'Beban Kendaraan (BBM, servis, pajak)', '5-2200' => 'Beban Pemeliharaan Aset', '5-2300' => 'Beban Penyusutan'] as $code => $name) {
            $add($code, $name, 'beban', '5-2', ['statement_group' => 'Beban operasional']);
        }
        $add('5-3', 'Beban Umum', 'beban', '5', $h + ['statement_group' => 'Beban umum']);
        foreach (['5-3100' => 'Beban Administrasi & ATK', '5-3200' => 'Beban Komunikasi & Internet', '5-3300' => 'Beban Bank', '5-3900' => 'Beban Lain-lain'] as $code => $name) {
            $add($code, $name, 'beban', '5-3', ['statement_group' => 'Beban umum']);
        }
    }
}
