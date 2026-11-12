<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Rata-rata tertimbang & hard-stop stok (PRD 9.10). */
class StockLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_weighted_average_and_negative_stock_stop()
    {
        $this->seed([\Database\Seeders\RolePermissionSeeder::class, \Database\Seeders\CoaSeeder::class, \Database\Seeders\CostCenterSeeder::class, \Database\Seeders\WarehouseSeeder::class]);
        $user = User::factory()->create();
        $item = Item::create(['code' => 'BHN-001', 'name' => 'Beras', 'category' => 'bahan_pangan', 'unit' => 'kg',
            'inventory_account_id' => \App\Models\CoaAccount::byCode('11810000')->id, 'expense_account_id' => \App\Models\CoaAccount::byCode('52110000')->id]);
        [$a, $b] = [Warehouse::byCode('DPR'), Warehouse::byCode('UMM')];
        $ledger = app(StockLedger::class);

        $ledger->record('masuk_beli', today()->toDateString(), $item, $a, 10, $user, null, ['unit_cost' => 10000]);
        $ledger->record('masuk_beli', today()->toDateString(), $item, $b, 30, $user, null, ['unit_cost' => 14000]);
        $this->assertSame('13000.0000', $item->fresh()->avg_cost, '(10×10.000 + 30×14.000) ÷ 40');
        $out = $ledger->record('keluar_pakai', today()->toDateString(), $item, $a, 4, $user);
        $this->assertSame(['-4.000', '13000.0000', '-52000.00'], [$out->qty, $out->unit_cost, $out->total_cost]);
        $ledger->record('masuk_panen', today()->toDateString(), $item, $a, 4, $user);
        $this->assertSame('11700.0000', $item->fresh()->avg_cost, 'panen bernilai 0 menurunkan rata-rata: 36×13.000 ÷ 40');

        try {
            $ledger->record('keluar_pakai', today()->toDateString(), $item, $a, 10.5, $user); // gudang A tinggal 10
            $this->fail('stok minus harus ditolak');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('sisa 10 kg, dibutuhkan 10,5 kg', $e->errors()['qty'][0]);
        }
        $this->assertSame(40.0, StockLedger::balance($item->id));

        \App\Models\FiscalPeriod::forDate(today())->update(['status' => 'closed']);
        $this->expectException(ValidationException::class);
        $ledger->record('masuk_beli', today()->toDateString(), $item, $a, 1, $user, null, ['unit_cost' => 1]);
    }
}
