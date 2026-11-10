<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\CoaAccount;
use App\Models\Item;
use App\Models\StockMove;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Barang & kartu stok (PRD 9.10). */
class ItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:stock-view')->only(['index', 'show']);
        $this->middleware('permission:stock-create')->only(['create', 'store']);
        $this->middleware('permission:stock-update')->only(['edit', 'update']);
    }

    /** Daftar barang + saldo per gudang. */
    public function index(Request $request)
    {
        $warehouses = Warehouse::orderBy('id')->get();
        $balances = $warehouses->mapWithKeys(fn ($w) => [$w->id => Item::balances($w->id)]);
        $items = Item::when($request->filled('kategori'), fn ($q) => $q->where('category', $request->input('kategori')))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%' . $request->input('q') . '%'))->orderBy('name')->get();

        return view('dashboard.item.index', compact('items', 'warehouses', 'balances'));
    }

    /** Kartu stok: mutasi + saldo berjalan; ?format=csv. */
    public function show(Request $request, Item $item)
    {
        $warehouseId = $request->integer('gudang') ?: null;
        $moves = StockMove::with('warehouse', 'creator', 'journal')->where('item_id', $item->id)->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->orderBy('date')->orderBy('id')->get();
        $running = 0;
        $moves->each(function ($m) use (&$running) {
            $running = round($running + (float) $m->qty, 3);
            $m->setAttribute('running', $running);
        });
        if ($request->input('format') === 'csv') {
            return response()->streamDownload(function () use ($moves) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['tanggal', 'gudang', 'tipe', 'qty', 'harga_satuan', 'nilai', 'saldo', 'catatan']);
                foreach ($moves as $m) {
                    fputcsv($out, [$m->date->toDateString(), $m->warehouse->name, $m->typeLabel(), $m->qty, $m->unit_cost, $m->total_cost, $m->running, $m->note]);
                }
                fclose($out);
            }, "kartu-stok-{$item->code}.csv", ['Content-Type' => 'text/csv']);
        }

        return view('dashboard.item.show', ['item' => $item, 'moves' => $moves->reverse()->values(), 'warehouses' => Warehouse::orderBy('id')->get(), 'warehouseId' => $warehouseId]);
    }

    public function create(Request $request)
    {
        $category = $request->input('kategori', 'bahan_pangan');

        return view('dashboard.item.form', ['item' => new Item(['category' => $category, 'is_active' => true]), 'accounts' => $this->accounts()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $item = Item::create($data + ['code' => Item::nextCode($data['category'])]);

        return redirect()->route('item.show', $item)->with('success', "Barang {$item->code} ditambahkan.");
    }

    public function edit(Item $item)
    {
        return view('dashboard.item.form', ['item' => $item, 'accounts' => $this->accounts()]);
    }

    public function update(Request $request, Item $item)
    {
        $item->update($this->validated($request, $item));

        return redirect()->route('item.show', $item)->with('success', 'Barang diubah.');
    }

    private function validated(Request $request, ?Item $item = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'category' => ['required', Rule::in(array_keys(config('mantaaba.item_categories')))],
            'unit' => 'required|string|max:20',
            'min_stock' => 'nullable|numeric|min:0',
            'inventory_account_id' => ['nullable', Rule::exists('coa_accounts', 'id')->where('type', 'aset')->where('is_header', false)],
            'expense_account_id' => ['nullable', Rule::exists('coa_accounts', 'id')->where('type', 'beban')->where('is_header', false)],
            'is_active' => 'boolean',
        ]);
        [, $inventory, $expense] = config("mantaaba.item_categories.{$data['category']}");
        $data['inventory_account_id'] ??= CoaAccount::byCode($inventory)->id;
        $data['expense_account_id'] ??= CoaAccount::byCode($expense)->id;
        $data['min_stock'] ??= 0;
        $data['is_active'] = $request->boolean('is_active', ! $item);

        return $data;
    }

    private function accounts()
    {
        return CoaAccount::postable()->whereIn('type', ['aset', 'beban'])->get()->groupBy('type');
    }
}
