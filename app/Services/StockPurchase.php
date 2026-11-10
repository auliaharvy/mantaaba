<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Journal;
use App\Models\StockMove;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Money;
use App\Support\PostingRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Pembelian stok (PRD 9.10, 9.16): masuk_beli per baris + voucher sistem CPV/BPV/PJV diajukan ke Bendahara. */
class StockPurchase
{
    public const PAYMENTS = ['tunai' => ['Tunai', 'CPV', 'purchase.cash'], 'transfer' => ['Transfer', 'BPV', 'purchase.bank'], 'kredit' => ['Kredit (utang)', 'PJV', 'purchase.payable']];

    public function __construct(private StockLedger $ledger, private JournalPoster $poster)
    {
    }

    /** $lines: [[item_id, qty, unit_price]]; $attachment: ['path' => ..., 'name' => ...] (nota). */
    public function create(array $header, array $lines, array $attachment, User $user): Journal
    {
        [$label, $type, $rule] = self::PAYMENTS[$header['payment']];
        if ($header['payment'] === 'kredit' && empty($header['party_id'])) {
            throw ValidationException::withMessages(['party_id' => 'Pembelian kredit wajib memilih pemasok.']);
        }
        $warehouse = Warehouse::findOrFail($header['warehouse_id']);
        $items = Item::whereIn('id', array_column($lines, 'item_id'))->get()->keyBy('id');

        return DB::transaction(function () use ($header, $lines, $attachment, $user, $type, $rule, $warehouse, $items) {
            $debits = [];
            $total = 0;
            foreach ($lines as $line) {
                $amount = (int) round((float) $line['qty'] * Money::cents($line['unit_price']));
                $account = $items[$line['item_id']]->inventory_account_id;
                $debits[$account] = ($debits[$account] ?? 0) + $amount;
                $total += $amount;
            }
            $counter = PostingRules::account($rule);
            $journalLines = collect($debits)->map(fn ($cents, $account) => ['coa_account_id' => $account, 'debit' => Money::decimal($cents)])->values()->all();
            $journalLines[] = ['coa_account_id' => $counter->id, 'credit' => Money::decimal($total), 'party_id' => $header['payment'] === 'kredit' ? $header['party_id'] : null];
            $summary = collect($lines)->map(fn ($l) => $items[$l['item_id']]->name . ' ' . StockLedger::qty((float) $l['qty']) . ' ' . $items[$l['item_id']]->unit)->implode(', ');

            $journal = $this->poster->createSystem($type, $header['date'], 'Pembelian stok: ' . \Illuminate\Support\Str::limit($summary, 200), $journalLines, $user, 'stock_purchase', null, [
                'party_id' => $header['party_id'] ?? null, 'reference_no' => $header['reference_no'] ?? null, 'attachments' => [$attachment],
            ]);
            foreach ($lines as $line) {
                $this->ledger->record('masuk_beli', $header['date'], $items[$line['item_id']], $warehouse, (float) $line['qty'], $user, $journal, [
                    'unit_cost' => (float) $line['unit_price'], 'party_id' => $header['party_id'] ?? null, 'journal_id' => $journal->id,
                ]);
            }

            return $journal;
        });
    }

    /** Batalkan pembelian: ditolak bila voucher sudah terposting (koreksi lewat voucher pembalik). */
    public function cancel(Journal $journal, User $user): void
    {
        abort_unless($journal->source_type === 'stock_purchase', 404);
        if ($journal->status === 'terposting') {
            throw ValidationException::withMessages(['status' => "Voucher {$journal->number} sudah terposting. Balik voucher itu dulu, lalu batalkan pembelian."]);
        }
        DB::transaction(function () use ($journal, $user) {
            $this->ledger->cancel(StockMove::where('journal_id', $journal->id)->where('type', 'masuk_beli')->get(), $user);
            if (in_array($journal->status, ['draft', 'diajukan'], true)) {
                $journal->forceFill(['status' => 'ditolak', 'reject_reason' => 'Pembelian dibatalkan oleh ' . $user->name])->save();
            }
        });
    }
}
