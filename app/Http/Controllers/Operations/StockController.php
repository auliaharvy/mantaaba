<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\CoaAccount;
use App\Models\Item;
use App\Models\Journal;
use App\Models\Party;
use App\Models\StockMove;
use App\Models\StockOpname;
use App\Models\Warehouse;
use App\Services\StockLedger;
use App\Services\StockPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Mutasi manual, pembelian, stock opname (PRD 9.10, 9.16). */
class StockController extends Controller
{
    public function __construct(private StockLedger $ledger)
    {
        $this->middleware('permission:stock-view')->only(['purchases', 'opnames', 'opname']);
        $this->middleware('permission:stock-move')->only(['moveForm', 'move', 'purchaseForm', 'purchase', 'cancelPurchase']);
        $this->middleware('permission:stock-opname')->only(['startOpname', 'saveOpname']);
    }

    public function moveForm()
    {
        return view('dashboard.stock.move', ['items' => Item::where('is_active', true)->orderBy('name')->get(), 'warehouses' => Warehouse::orderBy('id')->get()]);
    }

    /** Mutasi manual: donasi barang masuk, pemakaian umum, rusak/hilang, transfer antargudang. */
    public function move(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:masuk_donasi,keluar_pakai,keluar_rusak,transfer',
            'date' => 'required|date|before_or_equal:today',
            'item_id' => 'required|exists:items,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'nullable|required_if:type,transfer|exists:warehouses,id|different:warehouse_id',
            'qty' => 'required|numeric|gt:0',
            'unit_cost' => 'nullable|required_if:type,masuk_donasi|numeric|min:0',
            'note' => 'nullable|required_if:type,keluar_rusak|string|max:255',
        ], ['note.required_if' => 'Jelaskan penyebab rusak/hilang.', 'unit_cost.required_if' => 'Isi nilai wajar per satuan.', 'to_warehouse_id.different' => 'Gudang tujuan harus berbeda.']);
        $item = Item::findOrFail($data['item_id']);
        if ($data['type'] === 'transfer') {
            $this->ledger->transfer($data['date'], $item, Warehouse::find($data['warehouse_id']), Warehouse::find($data['to_warehouse_id']), (float) $data['qty'], $request->user(), $data['note'] ?? null);
        } else {
            $this->ledger->record($data['type'], $data['date'], $item, (int) $data['warehouse_id'], (float) $data['qty'], $request->user(), null,
                ['unit_cost' => $data['unit_cost'] ?? 0, 'note' => $data['note'] ?? null]);
        }

        return redirect()->route('item.show', $item)->with('success', 'Mutasi stok dicatat.');
    }

    public function purchases()
    {
        $journals = Journal::where('source_type', 'stock_purchase')->with('party')->latest('date')->latest('id')->paginate(25);
        $moves = StockMove::with('item')->whereIn('journal_id', $journals->pluck('id'))->where('type', 'masuk_beli')->get()->groupBy('journal_id');

        return view('dashboard.stock.purchases', compact('journals', 'moves'));
    }

    public function purchaseForm(Request $request)
    {
        return view('dashboard.stock.purchase', [
            'items' => Item::where('is_active', true)->orderBy('name')->get(), 'warehouses' => Warehouse::orderBy('id')->get(),
            'parties' => Party::where('is_active', true)->whereIn('type', ['pemasok', 'lainnya'])->orderBy('name')->get(),
            'prefill' => collect($request->input('items', []))->map(fn ($qty, $id) => ['item_id' => (int) $id, 'qty' => (float) $qty])->values()->all(),
            'warehouseId' => $request->integer('gudang') ?: null,
        ]);
    }

    public function purchase(Request $request, StockPurchase $purchases)
    {
        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'warehouse_id' => 'required|exists:warehouses,id',
            'payment' => ['required', Rule::in(array_keys(StockPurchase::PAYMENTS))],
            'party_id' => 'nullable|required_if:payment,kredit|exists:parties,id',
            'reference_no' => 'nullable|string|max:50',
            'receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'nullable|exists:items,id',
            'lines.*.qty' => 'nullable|required_with:lines.*.item_id|numeric|gt:0',
            'lines.*.unit_price' => 'nullable|required_with:lines.*.item_id|numeric|min:0',
        ], ['receipt.required' => 'Foto/scan nota wajib diunggah.', 'party_id.required_if' => 'Pembelian kredit wajib memilih pemasok.']);
        $lines = array_values(array_filter($data['lines'], fn ($l) => ! empty($l['item_id'])));
        abort_if(! $lines, 422, 'Isi minimal satu barang.');
        $file = $request->file('receipt');
        $path = $file->storeAs('journals/' . now()->format('Y/m'), Str::random(24) . '.' . $file->extension(), 'local');
        $journal = $purchases->create($data, $lines, ['path' => $path, 'name' => $file->getClientOriginalName()], $request->user());

        return redirect()->route('stock.purchases')->with('success', 'Pembelian dicatat: stok bertambah dan voucher masuk antrean Bendahara.');
    }

    public function cancelPurchase(Request $request, Journal $journal, StockPurchase $purchases)
    {
        $purchases->cancel($journal, $request->user());

        return back()->with('success', 'Pembelian dibatalkan dan stok dikurangi kembali.');
    }

    public function opnames()
    {
        return view('dashboard.stock.opnames', ['opnames' => StockOpname::with('warehouse')->latest('date')->latest('id')->paginate(25), 'warehouses' => Warehouse::orderBy('id')->get()]);
    }

    /** Mulai opname: draf berisi saldo sistem semua barang aktif di gudang. */
    public function startOpname(Request $request)
    {
        $data = $request->validate(['warehouse_id' => 'required|exists:warehouses,id', 'date' => 'required|date|before_or_equal:today']);
        StockLedger::ensureOpen($data['date']);
        $balances = Item::balances($data['warehouse_id']);
        $lines = Item::where('is_active', true)->orderBy('name')->get()
            ->filter(fn ($i) => ($balances[$i->id] ?? 0) != 0 || $i->category !== 'hasil_panen')
            ->map(fn ($i) => ['item_id' => $i->id, 'system' => $balances[$i->id] ?? 0, 'physical' => null])->values()->all();
        $opname = StockOpname::create($data + ['status' => 'draft', 'lines' => $lines, 'created_by' => $request->user()->id]);

        return redirect()->route('stock.opname', $opname);
    }

    public function opname(StockOpname $opname)
    {
        return view('dashboard.stock.opname', ['opname' => $opname, 'items' => Item::whereIn('id', collect($opname->lines)->pluck('item_id'))->get()->keyBy('id')]);
    }

    /** Simpan hitungan fisik; finalize=1 → mutasi opname sebesar selisih (saldo sistem dihitung ulang saat final). */
    public function saveOpname(Request $request, StockOpname $opname)
    {
        abort_if($opname->status === 'final', 422, 'Opname sudah final.');
        $physical = $request->validate(['physical' => 'array', 'physical.*' => 'nullable|numeric|min:0', 'note' => 'nullable|string|max:255'])['physical'] ?? [];
        $lines = collect($opname->lines)->map(fn ($l) => ['physical' => isset($physical[$l['item_id']]) && $physical[$l['item_id']] !== '' ? (float) $physical[$l['item_id']] : null] + $l)->all();
        if (! $request->boolean('finalize')) {
            $opname->update(['lines' => $lines, 'note' => $request->input('note')]);

            return back()->with('success', 'Hitungan disimpan (draf).');
        }
        DB::transaction(function () use ($opname, $lines, $request) {
            StockLedger::ensureOpen($opname->date->toDateString());
            $balances = Item::balances($opname->warehouse_id);
            foreach ($lines as $i => $line) {
                if ($line['physical'] === null) {
                    continue;
                }
                $lines[$i]['system'] = $balances[$line['item_id']] ?? 0;
                $diff = round($line['physical'] - $lines[$i]['system'], 3);
                if ($diff != 0) {
                    $this->ledger->record('opname', $opname->date->toDateString(), $line['item_id'], $opname->warehouse_id, $diff, $request->user(), $opname, ['note' => 'Opname #' . $opname->id]);
                }
            }
            $opname->forceFill(['lines' => $lines, 'note' => $request->input('note'), 'status' => 'final', 'finalized_by' => $request->user()->id, 'finalized_at' => now()])->save();
        });

        return redirect()->route('stock.opname', $opname)->with('success', 'Opname final: selisih dicatat dan akan dijurnal saat tutup bulan.');
    }
}
