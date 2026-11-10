<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\CoaAccount;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\KitchenMenu;
use App\Models\MealPlan;
use App\Models\Member;
use App\Models\Room;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\JournalPoster;
use App\Services\KitchenService;
use App\Services\SkemaEnroller;
use App\Services\StockPurchase;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/** Volume data presentasi (PRD 13.5): 12 siswa, ±40 voucher terposting, ±4 minggu riwayat dapur. Dipanggil DemoSeeder; dilarang di produksi. */
class DemoVolumeSeeder extends Seeder
{
    public function run(): void
    {
        abort_if(app()->isProduction(), 500, 'DemoVolumeSeeder dilarang di produksi.');
        $this->students();
        $this->vouchers();
        $this->kitchenHistory();
    }

    /** Tambah siswa hingga 12 orang di angkatan berjalan, ditempatkan di kamar & didaftarkan ke skema. */
    private function students(): void
    {
        $batch = Batch::where('status', 'running')->first();
        $rooms = ['L' => Room::where('gender', 'L')->orderBy('code')->get(), 'P' => Room::where('gender', 'P')->get()];
        $names = [['Salman Alfarisi', 'L'], ['Ilham Ramadhan', 'L'], ['Hanif Abdullah', 'L'], ['Rafi Pratama', 'L'], ['Zaki Mubarak', 'L'],
            ['Nur Aisyah', 'P'], ['Siti Maryam', 'P'], ['Fatimah Azzahra', 'P'], ['Laila Nuraini', 'P']];
        foreach ($names as [$name, $gender]) {
            $room = $rooms[$gender]->first(fn ($r) => $r->residents()->count() < $r->capacity);
            $member = Member::create(['member_number' => Member::nextNumber(2026), 'name' => $name, 'category' => 'siswa', 'status' => 'aktif', 'phase' => 'inkubasi',
                'batch_id' => $batch->id, 'joined_at' => $batch->starts_at, 'district' => 'Sigaling', 'gender' => $gender, 'room_id' => $room?->id, 'integrity_pact_signed_at' => now()]);
            $member->startPhase('inkubasi');
            app(SkemaEnroller::class)->enrollMember($member);
        }
    }

    /** Donasi masuk & beban rutin terposting sejak awal pembukuan hingga ±40 voucher. */
    private function vouchers(): void
    {
        $poster = app(JournalPoster::class);
        $maker = User::where('email', 'pengurus@mail.com')->first();
        $approver = User::where('email', 'superadmin@mail.com')->first();
        $acc = fn ($code) => CoaAccount::byCode($code)->id;
        $cc = fn ($code) => CostCenter::byCode($code)->id;
        Storage::disk('local')->put('journals/demo/bukti.pdf', "%PDF-1.4\n%demo\n");
        $start = today()->startOfMonth()->subMonth();
        $days = max(1, (int) $start->diffInDays(today()));
        $expenses = [['5-1300', 'ASR', 'Listrik & air asrama', 450000], ['5-1100', 'PND', 'Modul & alat tulis kelas', 275000], ['5-3200', 'UMUM', 'Paket internet', 350000],
            ['5-1600', 'PND', 'Konsumsi kajian tajug', 300000], ['5-1500', 'PND', 'Obat P3K', 125000]];
        $posted = \App\Models\Journal::where('status', 'terposting')->count();
        for ($i = 0; $posted < 40; $i++) {
            $date = $start->copy()->addDays((int) floor($i * $days / 36))->min(today())->toDateString();
            [$header, $lines] = $i % 2 === 0
                ? [['voucher_type' => 'BRV', 'date' => $date, 'description' => 'Donasi rutin donatur tetap'], [['coa_account_id' => $acc('1-1202'), 'debit' => 1500000], ['coa_account_id' => $acc($i % 4 ? '4-1100' : '4-1200'), 'credit' => 1500000, 'cost_center_id' => $cc('PND')]]]
                : (function () use ($expenses, $i, $date, $acc, $cc) {
                    [$code, $center, $text, $amount] = $expenses[intdiv($i, 2) % count($expenses)];

                    return [['voucher_type' => 'BPV', 'date' => $date, 'description' => $text], [['coa_account_id' => $acc($code), 'debit' => $amount, 'cost_center_id' => $cc($center)], ['coa_account_id' => $acc('1-1201'), 'credit' => $amount]]];
                })();
            $journal = $poster->saveDraft($header, $lines, $maker);
            $journal->forceFill(['attachments' => [['path' => 'journals/demo/bukti.pdf', 'name' => 'bukti.pdf']]])->save();
            $poster->submit($journal, $maker, true);
            $poster->post($journal, $approver);
            $posted++;
        }
    }

    /** Riwayat dapur ±4 minggu: belanja awal lalu sarapan & makan siang dimasak setiap hari. */
    private function kitchenHistory(): void
    {
        $maker = User::where('email', 'pengurus@mail.com')->first();
        $start = today()->subDays(27)->max(today()->startOfMonth()->subMonth());
        $dapur = Warehouse::byCode('DPR');
        $item = fn ($name) => Item::where('name', $name)->first();
        $menus = KitchenMenu::pluck('id', 'name');
        $portions = KitchenService::residents() ?: 8;
        $days = (int) $start->diffInDays(today()) + 1;
        // stok sebulan sesuai porsi: nasi 2× sehari, telur pagi, tempe siang, minyak keduanya (+ cadangan)
        app(StockPurchase::class)->create(['date' => $start->toDateString(), 'warehouse_id' => $dapur->id, 'payment' => 'transfer'], [
            ['item_id' => $item('Beras')->id, 'qty' => ceil(0.2 * $portions * $days) + 10, 'unit_price' => 12500], ['item_id' => $item('Telur ayam')->id, 'qty' => ceil(0.06 * $portions * $days) + 2, 'unit_price' => 27000],
            ['item_id' => $item('Tempe')->id, 'qty' => $portions * $days + 10, 'unit_price' => 2500], ['item_id' => $item('Minyak goreng')->id, 'qty' => ceil(0.02 * $portions * $days) + 2, 'unit_price' => 17500],
        ], ['path' => 'journals/demo/bukti.pdf', 'name' => 'nota-bulanan.pdf'], $maker);
        $kitchen = app(KitchenService::class);
        for ($d = $start->copy(); $d->lt(today()); $d->addDay()) {
            foreach (['sarapan' => ['Nasi putih', 'Telur dadar'], 'makan_siang' => ['Nasi putih', 'Tempe orek']] as $meal => $names) {
                $plan = MealPlan::firstOrCreate(['date' => $d->toDateString(), 'meal' => $meal], ['menu_ids' => collect($names)->map(fn ($n) => $menus[$n])->all(), 'planned_portions' => $portions]);
                if ($plan->status !== 'dimasak') {
                    $kitchen->cook($plan, $plan->requirements(), ['actual_portions' => $portions, 'leftover_portions' => $d->day % 4 === 0 ? 1 : 0], $maker);
                }
            }
        }
    }
}
