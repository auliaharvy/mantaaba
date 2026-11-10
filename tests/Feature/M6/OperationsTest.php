<?php

namespace Tests\Feature\M6;

use App\Models\ActivityLog;
use App\Models\CoaAccount;
use App\Models\FarmCycle;
use App\Models\FarmPlot;
use App\Models\FiscalPeriod;
use App\Models\Harvest;
use App\Models\Item;
use App\Models\Journal;
use App\Models\KitchenMenu;
use App\Models\MealPlan;
use App\Models\Member;
use App\Models\Setting;
use App\Models\StockMove;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\FarmService;
use App\Services\JournalPoster;
use App\Services\KitchenService;
use App\Services\PeriodCloser;
use App\Services\StockLedger;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Stok, kebun & kolam, dapur, jurnal otomatis bulanan (PRD 9.10–9.12, 9.16, AC M6). */
class OperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private User $treasurer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
        Storage::fake('public');
        $this->staff = User::where('email', 'pengurus@mail.com')->firstOrFail(); // Pengurus Inti: stok, kebun, dapur
        $this->treasurer = User::factory()->create()->assignRole('pengurus')->givePackage('bendahara');
    }

    private function item(string $name): Item
    {
        return Item::where('name', $name)->firstOrFail();
    }

    private function wh(string $code): Warehouse
    {
        return Warehouse::byCode($code);
    }

    private function buy(array $lines, string $payment = 'tunai', array $extra = [])
    {
        return $this->actingAs($this->staff)->post(route('stock.purchase'), [
            'date' => today()->toDateString(), 'warehouse_id' => $this->wh('DPR')->id, 'payment' => $payment, 'receipt' => UploadedFile::fake()->image('nota.jpg'),
            'lines' => $lines,
        ] + $extra);
    }

    public function test_purchase_adds_stock_creates_voucher_and_outflow_beyond_balance_is_rejected()
    {
        $beras = $this->item('Beras');
        $before = StockLedger::balance($beras->id, $this->wh('DPR')->id);
        $this->buy([['item_id' => $beras->id, 'qty' => 10, 'unit_price' => 13000]])->assertSessionHasNoErrors();
        $journal = Journal::where('source_type', 'stock_purchase')->latest('id')->first();
        $this->assertSame(['CPV', 'diajukan'], [$journal->voucher_type, $journal->status]);
        $this->assertSame(['1-1401', '1-1102'], $journal->lines->map(fn ($l) => $l->account->code)->all());
        $this->assertSame('130000.00', $journal->lines->first()->debit);
        $this->assertEqualsWithDelta($before + 10, StockLedger::balance($beras->id, $this->wh('DPR')->id), 0.0001);
        $this->buy([['item_id' => $beras->id, 'qty' => 1, 'unit_price' => 13000]], 'kredit')->assertSessionHasErrors('party_id');
        $this->buy([])->assertSessionHasErrors('lines');

        // keluar melebihi saldo → ditolak dengan sisa stok
        $balance = StockLedger::balance($beras->id, $this->wh('DPR')->id);
        $this->actingAs($this->staff)->post(route('stock.move'), ['type' => 'keluar_pakai', 'date' => today()->toDateString(), 'item_id' => $beras->id,
            'warehouse_id' => $this->wh('DPR')->id, 'qty' => $balance + 1])->assertSessionHasErrors(['qty' => 'Stok Beras di Gudang Dapur tidak cukup: sisa ' . StockLedger::qty($balance) . ' kg, dibutuhkan ' . StockLedger::qty($balance + 1) . ' kg.']);
        $this->actingAs($this->staff)->post(route('stock.move'), ['type' => 'keluar_rusak', 'date' => today()->toDateString(), 'item_id' => $beras->id,
            'warehouse_id' => $this->wh('DPR')->id, 'qty' => 1])->assertSessionHasErrors('note');
        $this->actingAs($this->staff)->post(route('stock.move'), ['type' => 'transfer', 'date' => today()->toDateString(), 'item_id' => $beras->id,
            'warehouse_id' => $this->wh('DPR')->id, 'to_warehouse_id' => $this->wh('UMM')->id, 'qty' => 2])->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta($balance, StockLedger::balance($beras->id), 0.0001, 'transfer tidak mengubah saldo total');
        $this->actingAs(User::where('email', 'anggota@mail.com')->first())->get(route('stock.purchase-form'))->assertForbidden();
    }

    public function test_cancelling_purchase_with_posted_voucher_is_rejected()
    {
        $minyak = $this->item('Minyak goreng');
        $this->buy([['item_id' => $minyak->id, 'qty' => 4, 'unit_price' => 20000]])->assertSessionHasNoErrors();
        $journal = Journal::where('source_type', 'stock_purchase')->latest('id')->first();
        $balance = StockLedger::balance($minyak->id);
        app(JournalPoster::class)->post($journal, $this->treasurer);
        $this->actingAs($this->staff)->post(route('stock.purchase-cancel', $journal))->assertSessionHasErrors('status');
        $this->assertEqualsWithDelta($balance, StockLedger::balance($minyak->id), 0.0001);

        // setelah dibalik, pembelian boleh dibatalkan; voucher diajukan yang dibatalkan otomatis ditolak
        app(JournalPoster::class)->reverse($journal->fresh(), $this->treasurer);
        $this->actingAs($this->staff)->post(route('stock.purchase-cancel', $journal))->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta($balance - 4, StockLedger::balance($minyak->id), 0.0001);
        $this->buy([['item_id' => $minyak->id, 'qty' => 1, 'unit_price' => 20000]]);
        $pending = Journal::where('source_type', 'stock_purchase')->latest('id')->first();
        $this->actingAs($this->staff)->post(route('stock.purchase-cancel', $pending))->assertSessionHasNoErrors();
        $this->assertSame('ditolak', $pending->fresh()->status);
    }

    public function test_farm_log_uses_stock_and_harvest_to_kitchen_has_zero_value()
    {
        $cycle = FarmCycle::where('commodity', 'Cabai rawit')->firstOrFail();
        $pupuk = $this->item('Pupuk NPK');
        $before = StockLedger::balance($pupuk->id, $this->wh('KBN')->id);
        $this->actingAs($this->staff)->post(route('farm.log', $cycle), ['activity' => 'pupuk', 'date' => today()->toDateString(), 'qty' => 3,
            'item_id' => $pupuk->id, 'warehouse_id' => $this->wh('KBN')->id, 'photo' => UploadedFile::fake()->image('pupuk.jpg')])->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta($before - 3, StockLedger::balance($pupuk->id, $this->wh('KBN')->id), 0.0001);
        $this->actingAs($this->staff)->post(route('farm.log', $cycle), ['activity' => 'pupuk', 'date' => today()->toDateString(), 'qty' => 999,
            'item_id' => $pupuk->id, 'warehouse_id' => $this->wh('KBN')->id])->assertSessionHasErrors('qty');
        $this->assertSame(2, $cycle->logs()->where('activity', 'pupuk')->count(), 'log dibatalkan bila stok kurang');

        $cabai = $this->item('Cabai rawit');
        $dapur = StockLedger::balance($cabai->id, $this->wh('DPR')->id);
        $this->actingAs($this->staff)->post(route('farm.harvest', $cycle), ['date' => today()->toDateString(), 'qty' => 3, 'item_id' => $cabai->id,
            'destination' => 'dapur', 'warehouse_id' => $this->wh('DPR')->id, 'market_price' => 35000])->assertSessionHasNoErrors();
        $harvest = Harvest::latest('id')->first();
        $move = StockMove::where('source_type', $harvest->getMorphClass())->where('source_id', $harvest->id)->first();
        $this->assertSame(['masuk_panen', '0.00'], [$move->type, $move->total_cost]);
        $this->assertEqualsWithDelta($dapur + 3, StockLedger::balance($cabai->id, $this->wh('DPR')->id), 0.0001);
        $this->assertSame(Money::cents(105000), $harvest->valueCents(), 'nilai pasar tercatat');
    }

    public function test_harvest_sale_creates_sales_voucher_and_cycle_summary_matches()
    {
        $lele = FarmCycle::where('commodity', 'Lele')->firstOrFail();
        $this->actingAs($this->staff)->post(route('farm.harvest', $lele), ['date' => today()->toDateString(), 'qty' => 20, 'item_id' => $this->item('Lele')->id,
            'destination' => 'jual', 'warehouse_id' => $this->wh('KBN')->id, 'market_price' => 25000])->assertSessionHasErrors('sale_price');
        $this->actingAs($this->staff)->post(route('farm.harvest', $lele), ['date' => today()->toDateString(), 'qty' => 20, 'item_id' => $this->item('Lele')->id,
            'destination' => 'jual', 'warehouse_id' => $this->wh('KBN')->id, 'market_price' => 25000, 'sale_price' => 26000, 'payment' => 'kredit',
            'party_id' => \App\Models\Party::first()->id])->assertSessionHasNoErrors();
        $harvest = Harvest::latest('id')->first();
        $this->assertSame(['RJV', 'diajukan'], [$harvest->journal->voucher_type, $harvest->journal->status]);
        $this->assertSame(['1-1301', '4-2100'], $harvest->journal->lines->map(fn ($l) => $l->account->code)->all());
        $this->assertSame('KLM', $harvest->journal->lines[1]->costCenter->code);
        $this->assertEqualsWithDelta(0, StockLedger::balance($this->item('Lele')->id), 0.0001, 'panen dijual: masuk lalu keluar');

        // ringkasan: pakan 5 kg × 11.000 = 55.000; panen 20 kg dijual 26.000 → nilai 520.000; hidup (1000−40)/1000
        $s = FarmService::summary($lele->fresh());
        $this->assertSame(Money::cents(55000), $s['cost']);
        $this->assertSame(20.0, $s['harvest_qty']);
        $this->assertSame(Money::cents(2750), $s['hpp']);
        $this->assertSame(Money::cents(520000), $s['value']);
        $this->assertSame(Money::cents(465000), $s['surplus']);
        $this->assertSame(96.0, $s['survival']);
        $this->assertSame(16.7, $s['target_percent']);
        $this->assertSame('panen', $lele->fresh()->status);
        $this->actingAs($this->staff)->get(route('farm.cycle', $lele))->assertOk()->assertSee('96%');
    }

    public function test_closed_cycle_is_locked_and_reopen_is_logged()
    {
        $cycle = FarmCycle::where('commodity', 'Cabai rawit')->firstOrFail();
        $this->actingAs($this->staff)->post(route('farm.close', $cycle), ['status' => 'gagal'])->assertSessionHasErrors('failure_reason');
        $this->actingAs($this->staff)->post(route('farm.close', $cycle), ['status' => 'selesai'])->assertSessionHasNoErrors();
        $this->assertSame('kosong', $cycle->plot->fresh()->status);
        $this->actingAs($this->staff)->post(route('farm.log', $cycle), ['activity' => 'siram', 'date' => today()->toDateString()])->assertSessionHasErrors('status');
        $this->actingAs($this->staff)->post(route('farm.harvest', $cycle), ['date' => today()->toDateString(), 'qty' => 1, 'item_id' => $this->item('Cabai rawit')->id,
            'destination' => 'dapur', 'warehouse_id' => $this->wh('DPR')->id, 'market_price' => 1])->assertSessionHasErrors('status');

        $kebun = User::factory()->create()->assignRole('pengurus')->givePackage('siswa_kebun'); // farm-log tanpa farm-update
        $this->actingAs($kebun)->post(route('farm.reopen', $cycle), ['reason' => 'x'])->assertForbidden();
        $this->actingAs($this->staff)->post(route('farm.reopen', $cycle), [])->assertSessionHasErrors('reason');
        $this->actingAs($this->staff)->post(route('farm.reopen', $cycle), ['reason' => 'Masih ada panen susulan'])->assertSessionHasNoErrors();
        $this->assertSame('panen', $cycle->fresh()->status);
        $this->assertTrue(ActivityLog::where('action', 'farm_cycle_reopened')->where('subject_id', $cycle->id)->exists());
    }

    public function test_cooking_with_insufficient_ingredients_names_each_shortage()
    {
        $plan = MealPlan::where('status', 'rencana')->where('meal', 'makan_siang')->whereDate('date', today())->firstOrFail();
        $tempe = $this->item('Tempe');
        $cabai = $this->item('Cabai rawit');
        $tempeStock = StockLedger::balance($tempe->id, $this->wh('DPR')->id);
        $response = $this->actingAs($this->staff)->post(route('kitchen.cook', $plan), ['ingredients' => [$tempe->id => $tempeStock + 5, $cabai->id => 50], 'actual_portions' => 8]);
        $response->assertSessionHasErrors('ingredients');
        $message = session('errors')->first('ingredients');
        $this->assertStringContainsString('Tempe: kurang 5 potong', $message);
        $this->assertStringContainsString('Cabai rawit: kurang', $message);
        $this->assertSame('rencana', $plan->fresh()->status);
        $this->assertSame(0, StockMove::where('source_type', (new \App\Models\KitchenLog)->getMorphClass())->whereHas('item', fn ($q) => $q->where('name', 'Tempe'))->count(), 'tidak ada bahan yang keluar');

        $this->actingAs($this->staff)->post(route('kitchen.cook', $plan), ['ingredients' => [$tempe->id => 2], 'actual_portions' => 8, 'leftover_portions' => 1])->assertSessionHasNoErrors();
        $this->assertSame('dimasak', $plan->fresh()->status);
        $this->assertSame('5000.00', $plan->fresh()->log->cost_total, '2 potong × Rp 2.500');
    }

    public function test_cost_per_person_per_day_matches_manual_calculation()
    {
        Setting::current()->update(['meal_budget_per_person_day' => 15000]);
        $residents = KitchenService::residents();
        $this->assertGreaterThan(0, $residents);
        $date = today()->toDateString();
        $breakfast = \App\Models\KitchenLog::whereHas('plan', fn ($q) => $q->whereDate('date', $date))->sum('cost_total');
        $plan = MealPlan::where('meal', 'makan_siang')->whereDate('date', $date)->firstOrFail();
        // beras 0,5 kg × 12.000 + tempe 4 × 2.500 = 16.000
        $this->actingAs($this->staff)->post(route('kitchen.cook', $plan), ['ingredients' => [$this->item('Beras')->id => 0.5, $this->item('Tempe')->id => 4], 'actual_portions' => $residents])->assertSessionHasNoErrors();
        $total = Money::cents($breakfast) + Money::cents(16000);
        $day = KitchenService::daily($date, $date)->first();
        $this->assertSame($total, $day['cost']);
        $this->assertSame(intdiv($total, $residents), $day['per_person']);
        $this->actingAs($this->staff)->get(route('kitchen.index'))->assertOk()->assertSee(Money::format(Money::decimal(intdiv($total, $residents))));
        $this->actingAs($this->staff)->get(route('kitchen.report'))->assertOk();

        // daftar belanja = kebutuhan rencana − stok gudang dapur
        $needs = KitchenService::needs(today()->startOfWeek()->toDateString(), today()->endOfWeek()->toDateString())->keyBy(fn ($r) => $r['item']->name);
        $this->assertEqualsWithDelta(max(0, $needs['Beras']['need'] - $needs['Beras']['stock']), $needs['Beras']['buy'], 0.0001);
    }

    public function test_monthly_stock_journal_is_idempotent_and_blocks_new_moves()
    {
        $period = FiscalPeriod::forDate(today());
        $closer = app(PeriodCloser::class);
        $this->assertGreaterThanOrEqual(1, $closer->generate($period, $this->treasurer)); // persediaan (+ penyusutan aset M7)
        $this->assertSame(0, $closer->generate($period, $this->treasurer), 'dijalankan ulang tidak dobel');
        $journal = Journal::where('source_type', 'monthly:stock')->where('source_id', $period->id)->sole();
        $this->assertSame('AJV', $journal->voucher_type);
        $this->assertSame($journal->lines->sum(fn ($l) => Money::cents($l->debit)), $journal->lines->sum(fn ($l) => Money::cents($l->credit)));
        // pemakaian dapur → beban konsumsi (DPR); pemakaian kebun → beban produksi bertanda siklus
        $consumption = $journal->lines->first(fn ($l) => $l->account->code === '5-1200');
        $this->assertSame('DPR', $consumption->costCenter->code);
        $kitchenCost = -StockMove::whereBetween('date', [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()])
            ->where('type', 'keluar_pakai')->whereHas('item', fn ($q) => $q->where('expense_account_id', CoaAccount::byCode('5-1200')->id))->get()->sum(fn ($m) => Money::cents($m->total_cost));
        $this->assertSame($kitchenCost, Money::cents($consumption->debit));
        $this->assertTrue($journal->lines->contains(fn ($l) => $l->account->code === '5-1400' && $l->farm_cycle_id));

        // setelah rekap dibuat, mutasi baru di periode itu ditolak
        $this->actingAs($this->staff)->post(route('stock.move'), ['type' => 'keluar_pakai', 'date' => today()->toDateString(), 'item_id' => $this->item('Beras')->id,
            'warehouse_id' => $this->wh('DPR')->id, 'qty' => 1])->assertSessionHasErrors('date');
        // ringkasan siklus tidak menghitung ganda rekap bulanan yang terposting
        $cycle = FarmCycle::where('commodity', 'Lele')->first();
        $before = FarmService::summary($cycle)['cost'];
        app(JournalPoster::class)->post($journal, User::where('email', 'superadmin@mail.com')->first());
        $this->assertSame($before, FarmService::summary($cycle->fresh())['cost']);
    }

    public function test_opname_finalizes_differences_and_journals_gain_and_loss()
    {
        $this->actingAs($this->staff)->post(route('stock.opname-start'), ['warehouse_id' => $this->wh('DPR')->id, 'date' => today()->toDateString()])->assertRedirect();
        $opname = \App\Models\StockOpname::latest('id')->first();
        $beras = $this->item('Beras');
        $telur = $this->item('Telur ayam');
        $berasSystem = StockLedger::balance($beras->id, $this->wh('DPR')->id);
        $telurSystem = StockLedger::balance($telur->id, $this->wh('DPR')->id);
        $this->actingAs($this->staff)->get(route('stock.opname', $opname))->assertOk()->assertSee('Beras');
        $this->actingAs($this->staff)->post(route('stock.opname-save', $opname), ['physical' => [$beras->id => $berasSystem - 1, $telur->id => $telurSystem + 0.5], 'finalize' => 1])->assertSessionHasNoErrors();
        $this->assertSame('final', $opname->fresh()->status);
        $this->assertEqualsWithDelta($berasSystem - 1, StockLedger::balance($beras->id, $this->wh('DPR')->id), 0.0001);
        $this->assertSame(2, StockMove::where('type', 'opname')->count());
        $this->assertTrue(ActivityLog::where('subject_type', (new StockMove)->getMorphClass())->exists(), 'opname tercatat di log aktivitas');

        $journal = app(\App\Services\StockMonthlyJournal::class)->generate(FiscalPeriod::forDate(today()), $this->treasurer);
        $codes = $journal->lines->map(fn ($l) => $l->account->code);
        $this->assertTrue($codes->contains('5-3900') && $codes->contains('4-3100'), 'opname kurang → beban lain-lain, lebih → pendapatan lain');
    }

    public function test_posting_rules_can_be_changed_by_super_admin_only()
    {
        $admin = User::where('email', 'superadmin@mail.com')->first();
        $this->actingAs($this->staff)->get(route('posting-rule.edit'))->assertForbidden();
        $this->actingAs($admin)->get(route('posting-rule.edit'))->assertOk()->assertSee('Pembelian stok tunai');
        $rules = collect(config('mantaaba.posting_rule_labels'))->keys()->mapWithKeys(fn ($k) => [str_replace('.', '__', $k) => config("mantaaba.posting_rules.{$k}")])->all();
        $rules['purchase__cash'] = '1-1101';
        $this->actingAs($admin)->put(route('posting-rule.update'), ['rules' => $rules])->assertSessionHasNoErrors();
        $this->assertSame(['purchase.cash' => '1-1101'], Setting::current()->posting_accounts);
        $this->buy([['item_id' => $this->item('Beras')->id, 'qty' => 1, 'unit_price' => 1000]]);
        $this->assertSame('1-1101', Journal::where('source_type', 'stock_purchase')->latest('id')->first()->lines->last()->account->code);
    }

    public function test_pages_render()
    {
        foreach (['item.index', 'item.create', 'stock.move-form', 'stock.purchases', 'stock.purchase-form', 'stock.opnames', 'farm.index', 'farm.plot-create',
            'kitchen.index', 'kitchen.menus', 'kitchen.menu-create', 'kitchen.plans', 'kitchen.needs', 'kitchen.report'] as $route) {
            $this->actingAs($this->staff)->get(route($route))->assertOk();
        }
        $this->actingAs($this->staff)->get(route('item.show', [$this->item('Beras'), 'format' => 'csv']))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($this->staff)->post(route('kitchen.plans-save'), ['plans' => [today()->addDays(5)->toDateString() => ['makan_malam' => ['menu_ids' => [KitchenMenu::first()->id], 'portions' => 9]]]])->assertSessionHasNoErrors();
        $this->assertSame(9, MealPlan::whereDate('date', today()->addDays(5))->where('meal', 'makan_malam')->first()->planned_portions);
        $this->actingAs($this->staff)->post(route('farm.plot-store'), ['type' => 'kolam', 'code' => 'K-02', 'name' => 'Kolam Nila', 'size_unit' => 'm3'])->assertSessionHasNoErrors();
        $plot = FarmPlot::where('code', 'K-02')->first();
        $this->actingAs($this->staff)->post(route('farm.cycle-store', $plot), ['commodity' => 'Nila', 'started_at' => today()->toDateString(), 'initial_qty' => 500, 'initial_unit' => 'ekor'])->assertRedirect();
        $this->assertSame('aktif', $plot->fresh()->status);
        $this->actingAs(User::where('email', 'anggota@mail.com')->first())->get(route('kitchen.index'))->assertForbidden();
    }
}
