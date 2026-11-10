<?php

namespace Database\Seeders;

use App\Models\CoaAccount;
use App\Models\CostCenter;
use App\Models\Party;
use App\Models\User;
use App\Services\JournalPoster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/** Contoh akuntansi (dev): pihak, saldo awal, voucher terposting/diajukan/draf. */
class AccountingDemoSeeder extends Seeder
{
    public function run(JournalPoster $poster): void
    {
        abort_if(app()->isProduction(), 500, 'AccountingDemoSeeder dilarang di produksi.');
        $admin = User::where('email', 'superadmin@mail.com')->first();
        $pengurus = User::where('email', 'pengurus@mail.com')->first();
        $acc = fn (string $code) => CoaAccount::byCode($code)->id;
        $cc = fn (string $code) => CostCenter::byCode($code)->id;

        $toko = Party::create(['name' => 'Toko Berkah', 'type' => 'pemasok', 'phone' => '081311112222']);
        $donatur = Party::create(['name' => 'Hamba Allah', 'type' => 'donatur']);
        Party::create(['name' => 'PLN', 'type' => 'lembaga']);

        $start = today()->startOfMonth()->subMonth();
        $opening = $poster->createSystem('MJV', $start->toDateString(), 'Saldo awal pembukuan', [
            ['coa_account_id' => $acc('1-1101'), 'debit' => 5000000], ['coa_account_id' => $acc('1-1102'), 'debit' => 1000000],
            ['coa_account_id' => $acc('1-1201'), 'debit' => 20000000], ['coa_account_id' => $acc('1-1202'), 'debit' => 10000000],
            ['coa_account_id' => $acc('1-2400'), 'debit' => 15000000],
            ['coa_account_id' => $acc('2-1100'), 'credit' => 2000000, 'party_id' => $toko->id],
            ['coa_account_id' => $acc('3-1100'), 'credit' => 49000000],
        ], $admin, 'opening_balance');
        $poster->post($opening, $admin);

        Storage::disk('local')->put('journals/demo/nota-demo.pdf', "%PDF-1.4\n%demo\n");
        $attach = [['path' => 'journals/demo/nota-demo.pdf', 'name' => 'nota-demo.pdf']];
        $make = function (array $header, array $lines, string $state) use ($poster, $pengurus, $admin, $attach) {
            $journal = $poster->saveDraft($header, $lines, $pengurus);
            $journal->forceFill(['attachments' => $attach])->save();
            if ($state !== 'draft') {
                $poster->submit($journal, $pengurus);
            }
            if ($state === 'posted') {
                $poster->post($journal, $admin);
            }
        };
        $make(['voucher_type' => 'CPV', 'date' => today()->subDays(3)->toDateString(), 'description' => 'Belanja bahan dapur minggu ini', 'party_id' => $toko->id, 'reference_no' => 'Nota 0921'],
            [['coa_account_id' => $acc('1-1401'), 'debit' => 412000, 'cost_center_id' => $cc('DPR')], ['coa_account_id' => $acc('1-1102'), 'credit' => 412000]], 'posted');
        $make(['voucher_type' => 'BRV', 'date' => today()->subDays(2)->toDateString(), 'description' => 'Donasi pendidikan', 'party_id' => $donatur->id],
            [['coa_account_id' => $acc('1-1202'), 'debit' => 2500000], ['coa_account_id' => $acc('4-1100'), 'credit' => 2500000, 'cost_center_id' => $cc('PND')]], 'posted');
        $make(['voucher_type' => 'BPV', 'date' => today()->subDay()->toDateString(), 'description' => 'Bayar listrik asrama'],
            [['coa_account_id' => $acc('5-1300'), 'debit' => 650000, 'cost_center_id' => $cc('ASR')], ['coa_account_id' => $acc('1-1201'), 'credit' => 650000]], 'submitted');
        $make(['voucher_type' => 'CPV', 'date' => today()->toDateString(), 'description' => 'ATK kelas SIA'],
            [['coa_account_id' => $acc('5-3100'), 'debit' => 85000, 'cost_center_id' => $cc('PND')], ['coa_account_id' => $acc('1-1101'), 'credit' => 85000]], 'draft');

        // M5b: anggaran tahun berjalan + konfirmasi donasi publik yang menunggu verifikasi
        foreach ([['5-1300', 'ASR', 6000000], ['5-3100', 'PND', 1200000]] as [$code, $center, $amount]) {
            \App\Models\Budget::create(['year' => today()->year, 'coa_account_id' => $acc($code), 'cost_center_id' => $cc($center), 'amount' => $amount]);
        }
        Storage::disk('local')->put('donations/demo-transfer.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
        \App\Models\Donation::create(['donor_name' => 'Ibu Sari', 'phone' => '081277778888', 'email' => 'sari@example.com', 'kind' => 'transfer', 'amount' => 500000,
            'transferred_at' => today()->toDateString(), 'coa_bank_account_id' => $acc('1-1202'), 'purpose' => 'pangan', 'proof' => 'donations/demo-transfer.png',
            'message' => 'Semoga berkah untuk adik-adik.']);
    }
}
