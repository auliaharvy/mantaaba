<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\AssetUsage;
use App\Models\CoaAccount;
use App\Models\CostCenter;
use App\Models\Journal;
use App\Models\User;
use App\Notifications\AssetReminder;
use App\Support\Money;
use App\Support\PostingRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/** Aset & kendaraan (PRD 9.17, 9.16): perolehan, pemakaian, perawatan, pelepasan — voucher sistem diajukan ke Bendahara. */
class AssetService
{
    /** Cara pencatatan perolehan → [label, tipe voucher, aturan akun lawan]. */
    public const RECORDINGS = [
        'tunai' => ['Beli tunai', 'CPV', 'asset_purchase.cash'], 'transfer' => ['Beli transfer', 'BPV', 'asset_purchase.bank'],
        'kredit' => ['Beli kredit (utang)', 'PJV', 'asset_purchase.payable'], 'hibah' => ['Hibah/wakaf', 'MJV', 'asset_purchase.grant'],
        'saldo_awal' => ['Aset lama (masuk saldo awal, tanpa voucher)', null, null],
    ];

    public function __construct(private JournalPoster $poster)
    {
    }

    /** Akun & umur bawaan dari kategori. */
    public static function defaults(string $category): array
    {
        [, , $asset, $accumulated, $life] = config("mantaaba.asset_categories.{$category}");

        return [
            'asset_account_id' => CoaAccount::byCode($asset)->id,
            'accumulated_account_id' => $accumulated ? CoaAccount::byCode($accumulated)->id : null,
            'expense_account_id' => $accumulated ? CoaAccount::byCode('5-2300')->id : null,
            'useful_life_months' => $life ?: null,
        ];
    }

    public static function costCenterId(Asset $asset): ?int
    {
        return CostCenter::byCode(config("mantaaba.asset_categories.{$asset->category}.5", 'UMUM'))?->id;
    }

    public function register(array $data, string $recording, ?int $partyId, User $user): Asset
    {
        if ($recording === 'kredit' && ! $partyId) {
            throw ValidationException::withMessages(['party_id' => 'Pembelian kredit wajib memilih pemasok.']);
        }

        return DB::transaction(function () use ($data, $recording, $partyId, $user) {
            $asset = Asset::create($data + ['code' => Asset::nextCode($data['category'])]);
            [, $type, $rule] = self::RECORDINGS[$recording];
            if ($type) {
                $amount = Money::decimal(Money::cents($asset->acquisition_value));
                $counter = ['coa_account_id' => PostingRules::account($rule)->id, 'credit' => $amount, 'party_id' => $recording === 'kredit' ? $partyId : null];
                if ($recording === 'hibah') {
                    $counter['cost_center_id'] = CostCenter::byCode('UMUM')->id;
                }
                $journal = $this->poster->createSystem($type, $asset->acquired_at->toDateString(), "Perolehan aset {$asset->code} {$asset->name}", [
                    ['coa_account_id' => $asset->asset_account_id, 'debit' => $amount, 'asset_id' => $asset->id], $counter,
                ], $user, $asset->getMorphClass(), $asset->id, ['party_id' => $partyId, 'attachments' => $asset->documents ? [$asset->documents[0]] : null]);
                $asset->forceFill(['journal_id' => $journal->id])->save();
            }

            return $asset;
        });
    }

    /** Mulai pakai kendaraan: ditolak bila masih dipakai (lock baris aset). */
    public function startUsage(Asset $asset, array $data, User $user): AssetUsage
    {
        return DB::transaction(function () use ($asset, $data, $user) {
            $asset = Asset::whereKey($asset->id)->lockForUpdate()->with('vehicle')->first();
            abort_if($asset->disposed_at, 422, 'Aset sudah dilepas.');
            if ($open = $asset->usages()->where('status', 'dipakai')->with('user')->first()) {
                throw ValidationException::withMessages(['asset' => "{$asset->name} sedang dipakai {$open->user?->name} sejak {$open->started_at->translatedFormat('j M H:i')}."]);
            }

            return $asset->usages()->create($data + ['user_id' => $user->id, 'started_at' => now(), 'status' => 'dipakai',
                'odometer_start' => $data['odometer_start'] ?? $asset->vehicle?->last_odometer]);
        });
    }

    /** Selesai pakai: odometer akhir ≥ awal; BBM → voucher CPV (beban kendaraan). */
    public function endUsage(AssetUsage $usage, array $data, User $user): AssetUsage
    {
        abort_unless($usage->status === 'dipakai', 422, 'Pemakaian ini sudah selesai.');
        if (isset($data['odometer_end'], $usage->odometer_start) && $data['odometer_end'] < $usage->odometer_start) {
            throw ValidationException::withMessages(['odometer_end' => "Odometer akhir tidak boleh lebih kecil dari odometer awal ({$usage->odometer_start} km)."]);
        }

        return DB::transaction(function () use ($usage, $data, $user) {
            $usage->update($data + ['ended_at' => now(), 'status' => 'kembali']);
            $asset = $usage->asset;
            if ($vehicle = $asset->vehicle) {
                $vehicle->update(['last_odometer' => max($vehicle->last_odometer, (int) $usage->odometer_end)]);
                $this->notifyKmService($asset->fresh('vehicle'));
            }
            if (Money::cents($usage->fuel_cost) > 0) {
                $journal = $this->expenseVoucher($asset, $usage->ended_at->toDateString(), "BBM {$asset->name}: {$usage->purpose}", $usage->fuel_cost, null,
                    $usage->receipt ? ['path' => $usage->receipt, 'name' => 'struk-bbm.' . pathinfo($usage->receipt, PATHINFO_EXTENSION)] : null, $user, $usage);
                $usage->forceFill(['journal_id' => $journal->id])->save();
            }

            return $usage;
        });
    }

    public function maintenance(Asset $asset, array $data, User $user): AssetMaintenance
    {
        return DB::transaction(function () use ($asset, $data, $user) {
            $record = $asset->maintenances()->create($data + ['created_by' => $user->id]);
            if ($vehicle = $asset->vehicle) {
                $updates = match ($record->type) {
                    'servis' => ['last_service_at' => $record->date, 'last_service_km' => $record->odometer ?? $vehicle->last_odometer],
                    'pajak' => $record->next_due_at ? ['tax_due_at' => $record->next_due_at] : [],
                    'stnk' => $record->next_due_at ? ['stnk_due_at' => $record->next_due_at] : [],
                    'kir' => $record->next_due_at ? ['kir_due_at' => $record->next_due_at] : [],
                    default => [],
                };
                if ($record->odometer) {
                    $updates['last_odometer'] = max($vehicle->last_odometer, $record->odometer);
                }
                $vehicle->update($updates);
            }
            if (Money::cents($record->cost) > 0) {
                $journal = $this->expenseVoucher($asset, $record->date->toDateString(), AssetMaintenance::TYPES[$record->type] . " {$asset->name}: {$record->description}", $record->cost,
                    $record->vendor_party_id, $record->attachment ? ['path' => $record->attachment, 'name' => 'bukti-' . $record->id . '.' . pathinfo($record->attachment, PATHINFO_EXTENSION)] : null, $user, $record);
                $record->forceFill(['journal_id' => $journal->id])->save();
            }

            return $record;
        });
    }

    /**
     * Pelepasan (jual/hapus): Dr akumulasi + Dr kas/bank (hasil) · Cr aset; selisih → keuntungan/kerugian.
     * Voucher: CRV/BRV bila ada hasil, MJV bila dihapus tanpa hasil.
     */
    public function dispose(Asset $asset, string $date, string $note, float $proceeds, ?int $accountId, User $user): Journal
    {
        abort_if($asset->disposed_at, 422, 'Aset sudah dilepas.');
        abort_if($asset->usages()->where('status', 'dipakai')->exists(), 422, 'Selesaikan pemakaian kendaraan dulu.');
        $proceedsCents = Money::cents($proceeds);
        $account = $accountId ? CoaAccount::find($accountId) : null;
        if ($proceedsCents > 0 && ! $account?->isCashOrBank()) {
            throw ValidationException::withMessages(['coa_account_id' => 'Pilih akun kas/bank penerima hasil penjualan.']);
        }
        $cost = Money::cents($asset->acquisition_value);
        $accumulated = $asset->accumulated_account_id ? $asset->accumulatedCents(true) : 0;
        $umum = CostCenter::byCode('UMUM')->id;
        $lines = [];
        if ($accumulated > 0) {
            $lines[] = ['coa_account_id' => $asset->accumulated_account_id, 'debit' => Money::decimal($accumulated), 'asset_id' => $asset->id];
        }
        if ($proceedsCents > 0) {
            $lines[] = ['coa_account_id' => $account->id, 'debit' => Money::decimal($proceedsCents)];
        }
        $lines[] = ['coa_account_id' => $asset->asset_account_id, 'credit' => Money::decimal($cost), 'asset_id' => $asset->id];
        $result = $proceedsCents + $accumulated - $cost;
        if ($result !== 0) {
            $lines[] = ['coa_account_id' => PostingRules::account($result > 0 ? 'asset_disposal.gain' : 'asset_disposal.loss')->id,
                $result > 0 ? 'credit' : 'debit' => Money::decimal(abs($result)), 'cost_center_id' => $umum, 'asset_id' => $asset->id];
        }
        $type = $proceedsCents > 0 ? ($account->is_bank ? 'BRV' : 'CRV') : 'MJV';

        return DB::transaction(function () use ($asset, $date, $note, $lines, $type, $user) {
            $journal = $this->poster->createSystem($type, $date, "Pelepasan aset {$asset->code} {$asset->name}: {$note}", $lines, $user, 'asset_disposal', $asset->id);
            $asset->forceFill(['disposed_at' => $date, 'disposal_note' => $note, 'condition' => 'dilepas', 'disposal_journal_id' => $journal->id])->save();

            return $journal;
        });
    }

    /** Ringkasan kendaraan: km, BBM, km/liter, biaya per km, perawatan tahun berjalan (sen). */
    public static function vehicleSummary(Asset $asset): array
    {
        $usages = $asset->usages()->where('status', 'kembali')->get();
        $km = $usages->sum(fn ($u) => $u->distance() ?? 0);
        $fuel = $usages->sum(fn ($u) => Money::cents($u->fuel_cost));
        $liters = (float) $usages->sum('fuel_liters');
        $maintenance = $asset->maintenances()->whereYear('date', today()->year)->get()->sum(fn ($m) => Money::cents($m->cost));
        $allMaintenance = $asset->maintenances()->get()->sum(fn ($m) => Money::cents($m->cost));

        return ['km' => $km, 'fuel' => $fuel, 'liters' => $liters, 'km_per_liter' => $liters > 0 ? round($km / $liters, 1) : null,
            'cost_per_km' => $km > 0 ? intdiv($fuel + $allMaintenance, $km) : null, 'maintenance_year' => $maintenance];
    }

    /** Penerima pengingat: PIC aset + pemegang asset-update (koordinator). */
    public static function reminderRecipients(Asset $asset)
    {
        return User::withPermission('asset-update')->push(...array_filter([$asset->pic]))->unique('id')->filter(fn ($u) => $u->is_active);
    }

    private function notifyKmService(Asset $asset): void
    {
        $next = $asset->vehicle->nextService()['km'];
        if ($next && $asset->vehicle->last_odometer >= $next - 500) {
            $left = $next - $asset->vehicle->last_odometer;
            Notification::send(self::reminderRecipients($asset), new AssetReminder($asset, $left > 0 ? "Servis {$left} km lagi (jadwal {$next} km)" : "Servis terlewat " . abs($left) . ' km'));
        }
    }

    private function expenseVoucher(Asset $asset, string $date, string $description, $amount, ?int $partyId, ?array $attachment, User $user, $source): Journal
    {
        $rule = $asset->isVehicle() ? 'vehicle' : 'maintenance';
        $amount = Money::decimal(Money::cents($amount));

        return $this->poster->createSystem('CPV', $date, \Illuminate\Support\Str::limit($description, 250), [
            ['coa_account_id' => PostingRules::account("{$rule}.expense")->id, 'debit' => $amount, 'cost_center_id' => self::costCenterId($asset), 'asset_id' => $asset->id],
            ['coa_account_id' => PostingRules::account("{$rule}.cash")->id, 'credit' => $amount],
        ], $user, $source->getMorphClass(), $source->id, ['party_id' => $partyId, 'attachments' => $attachment ? [$attachment] : null]);
    }
}
