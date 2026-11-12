<?php

namespace Tests\Feature\M7;

use App\Models\Asset;
use App\Models\FiscalPeriod;
use App\Models\Journal;
use App\Models\User;
use App\Notifications\AssetReminder;
use App\Services\AssetService;
use App\Services\DepreciationJournal;
use App\Services\JournalPoster;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Aset & kendaraan (PRD 9.17, AC M7). */
class AssetTest extends TestCase
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
        $this->staff = User::where('email', 'pengurus@mail.com')->firstOrFail();
        $this->treasurer = User::factory()->create()->assignRole('pengurus')->givePackage('bendahara');
    }

    private function car(): Asset
    {
        return Asset::where('name', 'Toyota Avanza')->firstOrFail();
    }

    public function test_register_asset_creates_acquisition_voucher_and_private_documents()
    {
        $this->actingAs($this->staff)->post(route('asset.store'), [
            'name' => 'Laptop admin', 'category' => 'perkantoran', 'condition' => 'baik', 'source' => 'beli', 'acquired_at' => today()->toDateString(),
            'acquisition_value' => 7200000, 'recording' => 'tunai', 'documents' => [UploadedFile::fake()->create('nota.pdf', 20, 'application/pdf')],
        ])->assertSessionHasNoErrors();
        $asset = Asset::where('name', 'Laptop admin')->firstOrFail();
        $this->assertSame('AST-KTR-001', $asset->code);
        $this->assertSame(48, $asset->useful_life_months, 'umur bawaan kategori');
        $this->assertSame(['CPV', 'diajukan'], [$asset->journal->voucher_type, $asset->journal->status]);
        $this->assertSame(['12140000', '11110000'], $asset->journal->lines->map(fn ($l) => $l->account->code)->all());
        $this->assertSame($asset->id, $asset->journal->lines->first()->asset_id);
        auth()->logout();
        $this->get(route('asset.document', [$asset, 0]))->assertRedirect('/login');
        $this->actingAs($this->staff)->get(route('asset.document', [$asset, 0]))->assertOk();
        $this->actingAs(User::where('email', 'anggota@mail.com')->first())->get(route('asset.show', $asset))->assertForbidden();
        $this->actingAs($this->staff)->post(route('asset.store'), ['name' => 'X', 'category' => 'tajug_asrama', 'condition' => 'baik', 'source' => 'beli',
            'acquired_at' => today()->toDateString(), 'acquisition_value' => 1000, 'recording' => 'kredit'])->assertSessionHasErrors('party_id');

        // label QR banyak sekaligus
        $pdf = $this->actingAs($this->staff)->get(route('asset.labels', ['ids' => Asset::pluck('id')->all()]))->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_vehicle_in_use_cannot_be_used_again_and_odometer_must_increase()
    {
        $car = $this->car();
        $this->actingAs($this->staff)->post(route('asset.usage-start', $car), ['purpose' => 'Antar siswa', 'destination' => 'Puskesmas'])->assertSessionHasNoErrors();
        $other = User::factory()->create()->assignRole('pengurus')->givePackage('koordinator');
        $this->actingAs($other)->post(route('asset.usage-start', $car), ['purpose' => 'Belanja'])->assertSessionHasErrors('asset');
        $usage = $car->usages()->where('status', 'dipakai')->first();
        $this->assertSame(84032, $usage->odometer_start, 'km awal = odometer terakhir');

        $this->actingAs($this->staff)->post(route('asset.usage-end', $usage), ['odometer_end' => 84000])->assertSessionHasErrors('odometer_end');
        $this->actingAs($this->staff)->post(route('asset.usage-end', $usage), ['odometer_end' => 84080, 'fuel_cost' => 150000, 'fuel_liters' => 10,
            'receipt' => UploadedFile::fake()->image('struk.jpg')])->assertSessionHasNoErrors();
        $usage->refresh();
        $this->assertSame(['kembali', 48], [$usage->status, $usage->distance()]);
        $this->assertSame(84080, $car->vehicle->fresh()->last_odometer);
        $this->assertSame(['CPV', '52210000', 'KND'], [$usage->journal->voucher_type, $usage->journal->lines[0]->account->code, $usage->journal->lines[0]->costCenter->code]);
        $this->assertNotEmpty($usage->journal->attachments, 'struk menjadi lampiran voucher');

        $summary = AssetService::vehicleSummary($car->fresh());
        $this->assertSame(32 + 48, $summary['km']);
        $this->assertSame(Money::cents(250000), $summary['fuel']);
        $this->assertSame(4.6, $summary['km_per_liter']); // 80 km ÷ 17,5 L
    }

    public function test_land_is_not_depreciated_and_accumulated_never_exceeds_cost_minus_residual()
    {
        $land = Asset::where('category', 'tanah')->firstOrFail();
        $this->assertFalse($land->isDepreciable());
        $this->assertSame([], $land->schedule());

        $start = today()->subMonths(2)->startOfMonth();
        $asset = app(AssetService::class)->register(['name' => 'Pompa air', 'category' => 'peralatan_kebun', 'condition' => 'baik', 'source' => 'beli',
            'acquired_at' => $start->toDateString(), 'acquisition_value' => 1000000, 'residual_value' => 100000, 'depreciation_start' => $start->toDateString()]
            + array_merge(AssetService::defaults('peralatan_kebun'), ['useful_life_months' => 3]), 'saldo_awal', null, $this->staff);
        $schedule = $asset->schedule();
        $this->assertCount(3, $schedule);
        $this->assertSame([Money::cents(300000), Money::cents(300000), Money::cents(300000)], array_column($schedule, 'amount'));

        $admin = User::where('email', 'superadmin@mail.com')->first();
        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $journal = app(DepreciationJournal::class)->generate(FiscalPeriod::forDate($month), $this->treasurer);
            if ($journal) {
                app(JournalPoster::class)->post($journal, $admin);
            }
            $this->assertLessThanOrEqual($asset->depreciableCents(), $asset->accumulatedCents());
        }
        $this->assertSame(Money::cents(900000), $asset->accumulatedCents());
        $this->assertSame(Money::cents(100000), $asset->bookValueCents());
        $this->assertFalse(Journal::where('source_type', 'monthly:depreciation')->get()->flatMap->lines->contains('asset_id', $land->id), 'tanah tidak pernah dijurnal');

        // pelepasan: jual 250.000 → Dr akumulasi 900.000 + kas 250.000, Cr aset 1.000.000, untung 150.000
        $journal = app(AssetService::class)->dispose($asset, today()->toDateString(), 'Dijual', 250000, \App\Models\CoaAccount::byCode('11110000')->id, $this->staff);
        $this->assertSame('CRV', $journal->voucher_type);
        $gain = $journal->lines->first(fn ($l) => $l->account->code === '79000000');
        $this->assertSame('150000.00', $gain->credit);
        $this->assertSame('dilepas', $asset->fresh()->condition);
    }

    public function test_reminders_are_sent_exactly_at_h30_and_h7()
    {
        Notification::fake();
        $vehicle = $this->car()->vehicle;
        $vehicle->update(['tax_due_at' => today()->addDays(30), 'stnk_due_at' => today()->addDays(29), 'kir_due_at' => null, 'service_interval_days' => null]);
        $this->artisan('assets:reminders')->assertSuccessful();
        Notification::assertSentTo($this->staff, AssetReminder::class, fn ($n) => str_contains($n->message, 'Pajak kendaraan') && str_contains($n->message, '30 hari'));
        Notification::assertSentToTimes($this->staff, AssetReminder::class, 1);

        Notification::fake();
        $vehicle->update(['tax_due_at' => today()->addDays(8), 'stnk_due_at' => today()->addDays(7)]);
        $this->artisan('assets:reminders')->assertSuccessful();
        Notification::assertSentToTimes($this->staff, AssetReminder::class, 1);
        Notification::assertSentTo($this->staff, AssetReminder::class, fn ($n) => str_contains($n->message, 'STNK') && str_contains($n->message, '7 hari'));

        Notification::fake();
        $vehicle->update(['stnk_due_at' => today()->addDays(6), 'last_service_at' => today()->subDays(173), 'service_interval_days' => 180]);
        $this->artisan('assets:reminders')->assertSuccessful();
        Notification::assertSentTo($this->staff, AssetReminder::class, fn ($n) => str_contains($n->message, 'Servis berkala'));
    }

    public function test_maintenance_updates_vehicle_and_creates_voucher()
    {
        $car = $this->car();
        $this->actingAs($this->staff)->post(route('asset.maintenance', $car), ['date' => today()->toDateString(), 'type' => 'servis', 'description' => 'Servis 85.000 km',
            'cost' => 650000, 'odometer' => 85000])->assertSessionHasNoErrors();
        $vehicle = $car->vehicle->fresh();
        $this->assertSame([85000, 85000], [$vehicle->last_service_km, $vehicle->last_odometer]);
        $this->assertSame(90000, $vehicle->nextService()['km']);
        $record = $car->maintenances()->first();
        $this->assertSame('52210000', $record->journal->lines[0]->account->code);
        $this->actingAs($this->staff)->post(route('asset.maintenance', $car), ['date' => today()->toDateString(), 'type' => 'pajak', 'description' => 'Pajak tahunan',
            'cost' => 2100000, 'next_due_at' => today()->addYear()->toDateString()])->assertSessionHasNoErrors();
        $this->assertTrue($car->vehicle->fresh()->tax_due_at->isSameDay(today()->addYear()));
    }

    public function test_opening_balance_wizard_is_prefilled_from_asset_register()
    {
        $suggested = \App\Http\Controllers\OpeningBalanceController::assetSuggestions();
        $vehicleAccount = \App\Models\CoaAccount::byCode('12200000')->id;
        $this->assertSame('180000000.00', $suggested[$vehicleAccount]);
        $this->assertSame('27500000.00', $suggested[\App\Models\CoaAccount::byCode('12210000')->id]);
        // motor dibeli dengan voucher → tidak ikut saldo awal (nilai akun kendaraan hanya Avanza)
        Journal::where('source_type', 'opening_balance')->update(['status' => 'dibalik']);
        $this->actingAs(User::where('email', 'superadmin@mail.com')->first())->get(route('opening.show'))->assertOk()->assertSee('180000000.00');
    }

    public function test_pages_render()
    {
        foreach ([route('asset.index'), route('asset.create'), route('asset.create', ['kategori' => 'kendaraan']), route('asset.show', $this->car()), route('asset.edit', $this->car()),
            route('asset.show', Asset::where('category', 'tanah')->first())] as $url) {
            $this->actingAs($this->staff)->get($url)->assertOk();
        }
        $this->actingAs($this->staff)->put(route('asset.update', $this->car()), ['name' => 'Toyota Avanza Veloz', 'category' => 'kendaraan', 'condition' => 'baik', 'source' => 'beli',
            'acquired_at' => $this->car()->acquired_at->toDateString(), 'acquisition_value' => 180000000, 'vehicle' => ['plate_number' => 'B 1234 XYZ', 'kir_due_at' => today()->addMonths(2)->toDateString()]])->assertSessionHasNoErrors();
        $this->assertNotNull(Asset::where('name', 'Toyota Avanza Veloz')->first()->vehicle->kir_due_at);
    }
}
