<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\FarmCycle;
use App\Models\FarmLog;
use App\Models\FarmPlot;
use App\Models\Harvest;
use App\Models\Item;
use App\Models\Member;
use App\Models\Party;
use App\Models\Warehouse;
use App\Services\FarmService;
use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Kebun & kolam (PRD 9.11): kartu petak, siklus, log dari HP, panen. */
class FarmController extends Controller
{
    public function __construct(private FarmService $farm)
    {
        $this->middleware('permission:farm-view')->only(['index', 'cycle']);
        $this->middleware('permission:farm-create')->only(['createPlot', 'storePlot', 'createCycle', 'storeCycle']);
        $this->middleware('permission:farm-update')->only(['editPlot', 'updatePlot', 'close', 'reopen']);
        $this->middleware('permission:farm-log')->only(['log', 'harvest']);
    }

    public function index(Request $request)
    {
        $type = $request->input('tipe');
        $plots = FarmPlot::with('activeCycle', 'pic')->when($type, fn ($q) => $q->where('type', $type))->orderBy('code')->get();
        $month = Harvest::with('item')->whereBetween('date', [today()->startOfMonth()->toDateString(), today()->toDateString()])->get()
            ->groupBy(fn ($h) => $h->item->name)->map(fn ($g) => $g->sum('qty') . ' ' . $g->first()->unit);
        $summaries = $plots->filter->activeCycle->mapWithKeys(fn ($p) => [$p->id => FarmService::summary($p->activeCycle)]);

        return view('dashboard.farm.index', compact('plots', 'type', 'month', 'summaries'));
    }

    public function createPlot()
    {
        return view('dashboard.farm.plot', ['plot' => new FarmPlot(['type' => 'lahan', 'size_unit' => 'm2']), 'members' => $this->members()]);
    }

    public function storePlot(Request $request)
    {
        FarmPlot::create($this->plotData($request));

        return redirect()->route('farm.index')->with('success', 'Petak/kolam ditambahkan.');
    }

    public function editPlot(FarmPlot $plot)
    {
        return view('dashboard.farm.plot', ['plot' => $plot, 'members' => $this->members()]);
    }

    public function updatePlot(Request $request, FarmPlot $plot)
    {
        $plot->update($this->plotData($request, $plot));

        return redirect()->route('farm.index')->with('success', 'Petak/kolam diubah.');
    }

    public function createCycle(FarmPlot $plot)
    {
        abort_if($plot->activeCycle()->exists(), 422, 'Petak ini masih punya siklus berjalan.');

        return view('dashboard.farm.cycle-form', ['plot' => $plot, 'cycle' => new FarmCycle(['started_at' => today()])]);
    }

    public function storeCycle(Request $request, FarmPlot $plot)
    {
        abort_if($plot->activeCycle()->exists(), 422, 'Petak ini masih punya siklus berjalan.');
        $data = $request->validate([
            'commodity' => 'required|string|max:100', 'variety' => 'nullable|string|max:100',
            'started_at' => 'required|date', 'expected_harvest_at' => 'nullable|date|after:started_at',
            'initial_qty' => 'nullable|numeric|min:0', 'initial_unit' => 'nullable|string|max:20',
            'target_yield' => 'nullable|numeric|min:0', 'target_unit' => 'nullable|string|max:20', 'notes' => 'nullable|string|max:2000',
        ]);
        $cycle = $plot->cycles()->create($data);
        $plot->update(['status' => 'aktif']);

        return redirect()->route('farm.cycle', $cycle)->with('success', 'Siklus dimulai.');
    }

    public function cycle(FarmCycle $cycle)
    {
        $cycle->load('plot', 'logs.item', 'logs.creator', 'harvests.item', 'harvests.journal');

        return view('dashboard.farm.cycle', [
            'cycle' => $cycle, 'summary' => FarmService::summary($cycle),
            'items' => Item::where('is_active', true)->whereIn('category', ['bibit_benih', 'pupuk', 'pakan', 'obat_pertanian', 'habis_pakai', 'lainnya'])->orderBy('name')->get(),
            'harvestItems' => Item::where('is_active', true)->where('category', 'hasil_panen')->orderBy('name')->get(),
            'warehouses' => Warehouse::orderBy('id')->get(), 'parties' => Party::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function log(Request $request, FarmCycle $cycle)
    {
        $data = $request->validate([
            'activity' => ['required', Rule::in(array_keys(FarmLog::ACTIVITIES))],
            'date' => 'required|date|before_or_equal:today|after_or_equal:' . $cycle->started_at->toDateString(),
            'qty' => 'nullable|numeric|gt:0', 'unit' => 'nullable|string|max:20',
            'item_id' => 'nullable|exists:items,id', 'warehouse_id' => 'nullable|required_with:item_id|exists:warehouses,id',
            'description' => 'nullable|string|max:500', 'photo' => 'nullable|' . Upload::IMAGE_RULE,
        ], ['warehouse_id.required_with' => 'Pilih gudang asal barang.']);
        if ($request->hasFile('photo')) {
            $data['photo'] = Upload::store($request->file('photo'), 'farm', null, 1280);
        }
        if (! empty($data['item_id']) && empty($data['unit'])) {
            $data['unit'] = Item::find($data['item_id'])->unit;
        }
        $this->farm->log($cycle, $data, $request->user());

        return back()->with('success', 'Log dicatat.');
    }

    public function harvest(Request $request, FarmCycle $cycle)
    {
        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today', 'qty' => 'required|numeric|gt:0', 'grade' => 'nullable|string|max:20',
            'item_id' => ['required', Rule::exists('items', 'id')->where('category', 'hasil_panen')],
            'destination' => ['required', Rule::in(array_keys(Harvest::DESTINATIONS))],
            'warehouse_id' => 'required|exists:warehouses,id', 'market_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|required_if:destination,jual|numeric|min:0',
            'payment' => ['nullable', 'required_if:destination,jual', Rule::in(array_keys(Harvest::PAYMENTS))],
            'party_id' => 'nullable|required_if:payment,kredit|exists:parties,id',
        ], ['sale_price.required_if' => 'Isi harga jual per satuan.', 'payment.required_if' => 'Pilih cara bayar.', 'party_id.required_if' => 'Penjualan kredit wajib memilih pembeli.']);
        if ($data['destination'] !== 'jual') {
            $data = array_merge($data, ['sale_price' => null, 'payment' => null]);
        }
        $this->farm->harvest($cycle, $data, $request->user());

        return back()->with('success', $data['destination'] === 'jual' ? 'Panen dicatat; voucher penjualan masuk antrean Bendahara.' : 'Panen dicatat.');
    }

    public function close(Request $request, FarmCycle $cycle)
    {
        $data = $request->validate(['status' => 'required|in:selesai,gagal', 'failure_reason' => 'nullable|string|max:500']);
        $this->farm->close($cycle, $data['status'], $data['failure_reason'] ?? null, $request->user());

        return back()->with('success', 'Siklus ditutup.');
    }

    public function reopen(Request $request, FarmCycle $cycle)
    {
        $reason = $request->validate(['reason' => 'required|string|max:500'], ['reason.required' => 'Alasan membuka ulang wajib diisi.'])['reason'];
        $this->farm->reopen($cycle, $reason, $request->user());

        return back()->with('success', 'Siklus dibuka ulang.');
    }

    private function plotData(Request $request, ?FarmPlot $plot = null): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(FarmPlot::TYPES))],
            'code' => ['required', 'string', 'max:20', Rule::unique('farm_plots')->ignore($plot)],
            'name' => 'required|string|max:100', 'size' => 'nullable|numeric|min:0', 'size_unit' => 'required|in:m2,m3',
            'location_note' => 'nullable|string|max:255', 'status' => ['nullable', Rule::in(array_keys(FarmPlot::STATUSES))],
            'pic_member_id' => 'nullable|exists:members,id', 'photo' => 'nullable|' . Upload::IMAGE_RULE,
        ]);
        unset($data['photo']);
        if ($request->hasFile('photo')) {
            $data['photo'] = Upload::store($request->file('photo'), 'farm', $plot?->photo, 1280);
        }
        $data['status'] ??= $plot?->status ?? 'kosong';

        return $data;
    }

    private function members()
    {
        return Member::where('status', 'aktif')->orderBy('name')->get(['id', 'name', 'member_number']);
    }
}
