<?php

namespace Database\Seeders;

use App\Models\FarmPlot;
use App\Models\Item;
use App\Models\KitchenMenu;
use App\Models\MealPlan;
use App\Models\Party;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\FarmService;
use App\Services\KitchenService;
use App\Services\StockPurchase;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/** Contoh operasional (dev): barang, pembelian, petak/kolam + siklus, menu, rencana & masak. */
class OperationsDemoSeeder extends Seeder
{
    public function run(StockPurchase $purchases, FarmService $farm, KitchenService $kitchen): void
    {
        abort_if(app()->isProduction(), 500, 'OperationsDemoSeeder dilarang di produksi.');
        $pengurus = User::where('email', 'pengurus@mail.com')->first();
        $dapur = Warehouse::byCode('DPR');
        $kebun = Warehouse::byCode('KBN');
        $dapur->update(['pic_user_id' => $pengurus->id]);
        $kebun->update(['pic_user_id' => $pengurus->id]);

        $items = [];
        foreach ([
            ['Beras', 'bahan_pangan', 'kg', 10], ['Minyak goreng', 'bahan_pangan', 'liter', 2], ['Telur ayam', 'bahan_pangan', 'kg', 2], ['Tempe', 'bahan_pangan', 'potong', 10],
            ['Cabai rawit', 'hasil_panen', 'kg', 0], ['Lele', 'hasil_panen', 'kg', 0], ['Bayam', 'hasil_panen', 'ikat', 0],
            ['Pupuk NPK', 'pupuk', 'kg', 5], ['Pakan lele', 'pakan', 'kg', 20], ['Bibit cabai', 'bibit_benih', 'batang', 0],
        ] as [$name, $category, $unit, $min]) {
            [, $inventory, $expense] = config("mantaaba.item_categories.{$category}");
            $items[$name] = Item::create(['code' => Item::nextCode($category), 'name' => $name, 'category' => $category, 'unit' => $unit, 'min_stock' => $min,
                'inventory_account_id' => \App\Models\CoaAccount::byCode($inventory)->id, 'expense_account_id' => \App\Models\CoaAccount::byCode($expense)->id]);
        }

        Storage::disk('local')->put('journals/demo/nota-belanja.pdf', "%PDF-1.4\n%demo\n");
        $nota = ['path' => 'journals/demo/nota-belanja.pdf', 'name' => 'nota-belanja.pdf'];
        $toko = Party::where('name', 'Toko Berkah')->first();
        $date = today()->subDays(min(5, today()->day - 1))->toDateString();
        $purchases->create(['date' => $date, 'warehouse_id' => $dapur->id, 'payment' => 'tunai', 'party_id' => $toko?->id, 'reference_no' => 'NB-101'], [
            ['item_id' => $items['Beras']->id, 'qty' => 25, 'unit_price' => 12000], ['item_id' => $items['Minyak goreng']->id, 'qty' => 5, 'unit_price' => 18000],
            ['item_id' => $items['Telur ayam']->id, 'qty' => 3, 'unit_price' => 28000], ['item_id' => $items['Tempe']->id, 'qty' => 20, 'unit_price' => 2500],
        ], $nota, $pengurus);
        $purchases->create(['date' => $date, 'warehouse_id' => $kebun->id, 'payment' => 'transfer', 'party_id' => $toko?->id], [
            ['item_id' => $items['Pupuk NPK']->id, 'qty' => 10, 'unit_price' => 12000], ['item_id' => $items['Pakan lele']->id, 'qty' => 30, 'unit_price' => 11000],
        ], $nota, $pengurus);

        $plots = [
            FarmPlot::create(['type' => 'lahan', 'code' => 'L-01', 'name' => 'Bedengan Timur', 'size' => 40, 'status' => 'aktif']),
            FarmPlot::create(['type' => 'lahan', 'code' => 'L-02', 'name' => 'Bedengan Barat', 'size' => 35]),
            FarmPlot::create(['type' => 'kolam', 'code' => 'K-01', 'name' => 'Kolam Lele', 'size' => 12, 'size_unit' => 'm3', 'status' => 'aktif']),
        ];
        $cabai = $plots[0]->cycles()->create(['commodity' => 'Cabai rawit', 'started_at' => today()->subDays(46), 'expected_harvest_at' => today()->addDays(44),
            'initial_qty' => 120, 'initial_unit' => 'batang', 'target_yield' => 40, 'target_unit' => 'kg']);
        $lele = $plots[2]->cycles()->create(['commodity' => 'Lele', 'variety' => 'Sangkuriang', 'started_at' => today()->subDays(30), 'expected_harvest_at' => today()->addDays(45),
            'initial_qty' => 1000, 'initial_unit' => 'ekor', 'target_yield' => 120, 'target_unit' => 'kg']);
        $day = today()->subDays(min(2, today()->day - 1))->toDateString();
        $farm->log($cabai, ['activity' => 'pupuk', 'date' => $day, 'qty' => 2, 'unit' => 'kg', 'item_id' => $items['Pupuk NPK']->id, 'warehouse_id' => $kebun->id, 'description' => 'Pupuk susulan'], $pengurus);
        $farm->log($lele, ['activity' => 'pakan', 'date' => $day, 'qty' => 5, 'unit' => 'kg', 'item_id' => $items['Pakan lele']->id, 'warehouse_id' => $kebun->id], $pengurus);
        $farm->log($lele, ['activity' => 'kematian', 'date' => $day, 'qty' => 40, 'unit' => 'ekor', 'description' => 'Hujan deras, air keruh'], $pengurus);
        $farm->harvest($cabai, ['date' => $day, 'qty' => 2, 'item_id' => $items['Cabai rawit']->id, 'destination' => 'dapur', 'warehouse_id' => $dapur->id, 'market_price' => 30000], $pengurus);

        $menu = fn ($name, $category, $ingredients) => KitchenMenu::create(['name' => $name, 'category' => $category, 'is_active' => true,
            'ingredients' => collect($ingredients)->map(fn ($qty, $item) => ['item_id' => $items[$item]->id, 'qty' => $qty])->values()->all()]);
        $nasi = $menu('Nasi putih', 'pokok', ['Beras' => 0.1]);
        $telur = $menu('Telur dadar', 'lauk', ['Telur ayam' => 0.06, 'Minyak goreng' => 0.01]);
        $tempe = $menu('Tempe orek', 'lauk', ['Tempe' => 1, 'Minyak goreng' => 0.01]);
        $sambal = $menu('Sambal cabai', 'pelengkap', ['Cabai rawit' => 0.01]);
        $portions = KitchenService::residents() ?: 8;
        foreach ([0, 1, 2] as $offset) {
            $d = today()->addDays($offset)->toDateString();
            MealPlan::create(['date' => $d, 'meal' => 'sarapan', 'menu_ids' => [$nasi->id, $telur->id], 'planned_portions' => $portions]);
            MealPlan::create(['date' => $d, 'meal' => 'makan_siang', 'menu_ids' => [$nasi->id, $tempe->id, $sambal->id], 'planned_portions' => $portions]);
        }
        $breakfast = MealPlan::whereDate('date', today())->where('meal', 'sarapan')->first();
        $kitchen->cook($breakfast, $breakfast->requirements(), ['actual_portions' => $portions, 'leftover_portions' => 1], $pengurus);
    }
}
