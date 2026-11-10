<?php

namespace App\Http\Controllers;

use App\Models\CoaAccount;
use App\Services\Ledger;
use Illuminate\Http\Request;

/** Buku besar, neraca saldo, buku kas/bank (PRD 9.13; laporan keuangan lengkap di M5b). */
class LedgerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:finance-report-view');
    }

    public function ledger(Request $request)
    {
        $data = $request->validate(['account' => 'nullable|exists:coa_accounts,id', 'from' => 'nullable|date', 'to' => 'nullable|date']);
        $from = $data['from'] ?? today()->startOfMonth()->toDateString();
        $to = $data['to'] ?? today()->toDateString();
        $account = isset($data['account']) ? CoaAccount::find($data['account']) : null;

        return view('dashboard.ledger.ledger', [
            'accounts' => CoaAccount::where('is_header', false)->orderBy('code')->get(), 'account' => $account, 'from' => $from, 'to' => $to,
            'ledger' => $account ? Ledger::ledger($account, $from, $to) : null,
        ]);
    }

    public function trialBalance(Request $request)
    {
        $until = $request->validate(['date' => 'nullable|date'])['date'] ?? today()->toDateString();
        $rows = Ledger::trialBalance($until);

        return view('dashboard.ledger.trial-balance', ['rows' => $rows, 'until' => $until, 'debit' => $rows->sum('debit'), 'credit' => $rows->sum('credit')]);
    }

    public function cashBook(Request $request)
    {
        $until = today()->toDateString();
        $accounts = CoaAccount::where('is_header', false)->where(fn ($q) => $q->where('is_cash', true)->orWhere('is_bank', true))->orderBy('code')->get()
            ->map(fn ($a) => ['account' => $a, 'balance' => Ledger::balanceCents($a->id, $until)]);

        return view('dashboard.ledger.cash-book', compact('accounts'));
    }
}
