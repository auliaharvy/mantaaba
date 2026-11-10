<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\CoaAccount;
use App\Models\CostCenter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Anggaran tahunan per akun beban/pendapatan & pusat biaya (PRD 9.13.5). Rincian bulanan → v2. */
class BudgetController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('coa-view'), 403);
        $year = $request->integer('tahun') ?: now()->year;

        return view('dashboard.budget.index', [
            'year' => $year,
            'rows' => \App\Services\FinancialReports::budgetRealization($year),
            'accounts' => CoaAccount::postable()->whereIn('type', ['beban', 'pendapatan'])->get(),
            'centers' => CostCenter::where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('coa-create'), 403);
        $data = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'coa_account_id' => ['required', Rule::exists('coa_accounts', 'id')->whereIn('type', ['beban', 'pendapatan'])->where('is_header', false)],
            'cost_center_id' => 'required|exists:cost_centers,id',
            'amount' => 'required|numeric|min:0|max:999999999999',
        ]);
        Budget::updateOrCreate(collect($data)->only(['year', 'coa_account_id', 'cost_center_id'])->all(), ['amount' => $data['amount']]);

        return back()->with('success', 'Anggaran disimpan.');
    }

    public function destroy(Budget $budget)
    {
        abort_unless(auth()->user()->can('coa-delete'), 403);
        $budget->delete();
        session()->flash('success', 'Anggaran dihapus.');

        return response()->noContent();
    }
}
