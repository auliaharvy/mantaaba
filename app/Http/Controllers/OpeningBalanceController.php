<?php

namespace App\Http\Controllers;

use App\Models\CoaAccount;
use App\Models\Journal;
use App\Models\Party;
use App\Models\Setting;
use App\Services\JournalPoster;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Wizard saldo awal (PRD 9.13.3): MJV sistem, selisih wajib 0, piutang/utang per pihak. */
class OpeningBalanceController extends Controller
{
    public function show(Request $request)
    {
        abort_unless($request->user()->can('period-close'), 403);
        $existing = Journal::where('source_type', 'opening_balance')->whereIn('status', ['diajukan', 'terposting'])->with('lines.account', 'lines.party')->first();

        return view('dashboard.opening.show', [
            'existing' => $existing,
            'accounts' => CoaAccount::postable()->where('is_receivable', false)->where('is_payable', false)->whereIn('type', ['aset', 'liabilitas', 'aset_neto'])->get(),
            'partyAccounts' => CoaAccount::postable()->where(fn ($q) => $q->where('is_receivable', true)->orWhere('is_payable', true))->get(),
            'parties' => Party::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'date' => Setting::current()->accounting_start_date,
            'suggested' => self::assetSuggestions(),
        ]);
    }

    /** Usulan saldo aset tetap dari register aset yang dicatat "aset lama" (tanpa voucher perolehan): nilai perolehan & akumulasi awal per akun. */
    public static function assetSuggestions(): array
    {
        $assets = \App\Models\Asset::whereNull('journal_id')->whereNull('disposed_at')->get();
        $sum = [];
        foreach ($assets as $asset) {
            $sum[$asset->asset_account_id] = ($sum[$asset->asset_account_id] ?? 0) + Money::cents($asset->acquisition_value);
            if ($asset->accumulated_account_id && Money::cents($asset->opening_accumulated) > 0) {
                $sum[$asset->accumulated_account_id] = ($sum[$asset->accumulated_account_id] ?? 0) + Money::cents($asset->opening_accumulated);
            }
        }

        return array_map(fn ($cents) => Money::decimal($cents), $sum);
    }

    public function store(Request $request, JournalPoster $poster)
    {
        abort_unless($request->user()->can('period-close'), 403);
        abort_if(Journal::where('source_type', 'opening_balance')->whereIn('status', ['diajukan', 'terposting'])->exists(), 422, 'Saldo awal sudah dibuat. Koreksi lewat Balik lalu buat ulang.');
        $data = $request->validate([
            'date' => 'required|date',
            'amounts' => 'array', 'amounts.*' => 'nullable|numeric|min:0',
            'party_rows' => 'array', 'party_rows.*.coa_account_id' => 'nullable|exists:coa_accounts,id',
            'party_rows.*.party_id' => 'nullable|required_with:party_rows.*.amount|exists:parties,id', 'party_rows.*.amount' => 'nullable|numeric|min:0',
        ], ['party_rows.*.party_id.required_with' => 'Saldo piutang/utang wajib per pihak.']);

        $lines = [];
        $accounts = CoaAccount::whereIn('id', array_merge(array_keys($data['amounts'] ?? []), array_column($data['party_rows'] ?? [], 'coa_account_id')))->get()->keyBy('id');
        $line = function (CoaAccount $account, $amount, ?int $partyId = null) use (&$lines) {
            if (Money::cents($amount) > 0) {
                $side = $account->normal_balance === 'debit' ? 'debit' : 'credit';
                $lines[] = ['coa_account_id' => $account->id, $side => $amount, 'party_id' => $partyId, 'memo' => 'Saldo awal'];
            }
        };
        foreach ($data['amounts'] ?? [] as $accountId => $amount) {
            if (($account = $accounts[$accountId] ?? null) && ! $account->is_receivable && ! $account->is_payable) {
                $line($account, $amount);
            }
        }
        foreach ($data['party_rows'] ?? [] as $row) {
            if (! empty($row['coa_account_id']) && ($account = $accounts[$row['coa_account_id']] ?? null)) {
                $line($account, $row['amount'] ?? 0, (int) $row['party_id']);
            }
        }
        if (count($lines) < 2) {
            throw ValidationException::withMessages(['amounts' => 'Isi saldo minimal dua akun.']);
        }

        $journal = DB::transaction(function () use ($poster, $data, $lines, $request) {
            $journal = $poster->createSystem('MJV', $data['date'], 'Saldo awal pembukuan', $lines, $request->user(), 'opening_balance');

            return $poster->post($journal, $request->user());
        });
        Setting::current()->update(['accounting_start_date' => $data['date']]);

        return redirect()->route('journal.show', $journal)->with('success', 'Saldo awal terposting.');
    }
}
