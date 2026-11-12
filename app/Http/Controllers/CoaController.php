<?php

namespace App\Http\Controllers;

use App\Models\CoaAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Bagan akun (PRD 9.13.1). Akun yang sudah punya jurnal tidak bisa dihapus atau diubah tipe/kodenya. */
class CoaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:coa-view')->only('index');
        $this->authorizeResource(CoaAccount::class, 'coa', ['except' => ['index', 'show']]);
    }

    public function index()
    {
        return view('dashboard.coa.index', ['accounts' => CoaAccount::withCount('lines')->orderBy('code')->get()]);
    }

    public function create(Request $request)
    {
        $parent = CoaAccount::find($request->integer('parent'));

        return view('dashboard.coa.form', ['account' => new CoaAccount(['parent_id' => $parent?->id, 'type' => $parent?->type ?? 'beban', 'is_active' => true,
            'restriction' => $parent?->restriction, 'cash_flow_group' => $parent?->cash_flow_group ?? 'operasi', 'statement_group' => $parent?->statement_group]), 'parents' => $this->parents()]);
    }

    public function store(Request $request)
    {
        CoaAccount::create($this->validated($request));

        return redirect()->route('coa.index')->with('success', 'Akun ditambahkan.');
    }

    public function edit(CoaAccount $coa)
    {
        return view('dashboard.coa.form', ['account' => $coa->loadCount('lines'), 'parents' => $this->parents($coa)]);
    }

    public function update(Request $request, CoaAccount $coa)
    {
        $data = $this->validated($request, $coa);
        if ($coa->lines()->exists() && ($data['type'] !== $coa->type || $data['code'] !== $coa->code || $data['is_header'])) {
            throw ValidationException::withMessages(['type' => 'Akun yang sudah dipakai jurnal tidak bisa diubah kode/tipenya atau dijadikan header. Nonaktifkan dan buat akun baru.']);
        }
        $coa->update($data);

        return redirect()->route('coa.index')->with('success', 'Akun diubah.');
    }

    public function destroy(CoaAccount $coa)
    {
        abort_if($coa->lines()->exists(), 422, 'Akun sudah dipakai jurnal; nonaktifkan saja.');
        abort_if($coa->children()->exists(), 422, 'Akun masih memiliki sub-akun.');
        $coa->delete();
        session()->flash('success', 'Akun dihapus.');

        return response()->noContent();
    }

    private function parents(?CoaAccount $except = null)
    {
        return CoaAccount::where('is_header', true)->when($except, fn ($q) => $q->whereKeyNot($except->id))->orderBy('code')->get()->mapWithKeys(fn ($a) => [$a->id => $a->label()]);
    }

    private function validated(Request $request, ?CoaAccount $account = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[0-9]+(-[0-9]+)*$/', Rule::unique('coa_accounts')->ignore($account)],
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(array_keys(CoaAccount::TYPES))],
            'parent_id' => 'nullable|exists:coa_accounts,id',
            'restriction' => ['nullable', Rule::in(array_keys(CoaAccount::RESTRICTIONS))],
            'cash_flow_group' => ['nullable', Rule::in(array_keys(CoaAccount::CASH_FLOW_GROUPS))],
            'statement_group' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ], ['code.regex' => 'Kode akun berupa angka (8 digit), mis. 52190000.']);
        foreach (['is_header', 'is_cash', 'is_bank', 'is_receivable', 'is_payable', 'is_contra', 'is_active'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }
        if ($data['is_cash'] && $data['is_bank']) {
            throw ValidationException::withMessages(['is_cash' => 'Pilih kas ATAU bank.']);
        }
        if (($data['is_cash'] || $data['is_bank'] || $data['is_receivable']) && $data['type'] !== 'aset') {
            throw ValidationException::withMessages(['type' => 'Akun kas, bank, dan piutang harus bertipe aset.']);
        }
        if ($data['is_payable'] && $data['type'] !== 'liabilitas') {
            throw ValidationException::withMessages(['type' => 'Akun utang harus bertipe liabilitas.']);
        }
        $normal = in_array($data['type'], ['aset', 'beban'], true) ? 'debit' : 'kredit';
        $data['normal_balance'] = $data['is_contra'] ? ($normal === 'debit' ? 'kredit' : 'debit') : $normal;
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
