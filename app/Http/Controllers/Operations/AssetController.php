<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\AssetUsage;
use App\Models\CoaAccount;
use App\Models\Party;
use App\Models\Setting;
use App\Models\User;
use App\Services\AssetService;
use App\Support\Upload;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/** Aset & kendaraan (PRD 9.17): register, label QR, pemakaian, perawatan, penyusutan, pelepasan. */
class AssetController extends Controller
{
    public function __construct(private AssetService $assets)
    {
        $this->middleware('permission:asset-view')->only(['index', 'show', 'document', 'labels']);
        $this->middleware('permission:asset-create')->only(['create', 'store']);
        $this->middleware('permission:asset-update')->only(['edit', 'update', 'dispose']);
        $this->middleware('permission:asset-usage')->only(['startUsage', 'endUsage']);
        $this->middleware('permission:asset-maintenance')->only(['maintenance']);
    }

    public function index(Request $request)
    {
        $assets = Asset::with('vehicle', 'pic')
            ->when($request->filled('kategori'), fn ($q) => $q->where('category', $request->input('kategori')))
            ->when($request->input('status') === 'dilepas', fn ($q) => $q->whereNotNull('disposed_at'), fn ($q) => $q->whereNull('disposed_at'))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%' . $request->input('q') . '%')->orWhere('code', 'like', '%' . $request->input('q') . '%')))
            ->orderBy('code')->get();
        $inUse = AssetUsage::where('status', 'dipakai')->pluck('asset_id')->flip();

        return view('dashboard.asset.index', compact('assets', 'inUse'));
    }

    public function create(Request $request)
    {
        $category = array_key_exists($request->input('kategori'), config('mantaaba.asset_categories')) ? $request->input('kategori') : 'peralatan_dapur';

        return view('dashboard.asset.form', ['asset' => new Asset(['category' => $category, 'acquired_at' => today(), 'source' => 'beli', 'condition' => 'baik'] + AssetService::defaults($category)),
            'vehicle' => null] + $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $recording = $request->validate(['recording' => ['required', Rule::in(array_keys(AssetService::RECORDINGS))], 'party_id' => 'nullable|exists:parties,id']);
        $asset = DB::transaction(function () use ($data, $recording, $request) {
            $asset = $this->assets->register($data['asset'], $recording['recording'], $recording['party_id'] ?? null, $request->user());
            $this->saveVehicle($asset, $data['vehicle']);

            return $asset;
        });

        return redirect()->route('asset.show', $asset)->with('success', "Aset {$asset->code} dicatat" . ($asset->journal_id ? '; voucher perolehan masuk antrean Bendahara.' : '.'));
    }

    public function show(Asset $asset)
    {
        $asset->load('vehicle', 'pic', 'journal', 'disposalJournal', 'usages.user', 'usages.journal', 'maintenances.journal', 'maintenances.vendor');

        return view('dashboard.asset.show', [
            'asset' => $asset, 'openUsage' => $asset->usages->firstWhere('status', 'dipakai'),
            'summary' => $asset->vehicle ? AssetService::vehicleSummary($asset) : null,
            'accumulated' => $asset->accumulated_account_id ? $asset->accumulatedCents() : 0,
            'schedule' => $asset->schedule(),
            'parties' => Party::where('is_active', true)->orderBy('name')->get(),
            'cashAccounts' => CoaAccount::postable()->where(fn ($q) => $q->where('is_cash', true)->orWhere('is_bank', true))->get(),
        ]);
    }

    public function edit(Asset $asset)
    {
        return view('dashboard.asset.form', ['asset' => $asset, 'vehicle' => $asset->vehicle] + $this->formData());
    }

    /** Nilai perolehan & akun terkunci setelah ada voucher (koreksi lewat Balik/voucher manual). */
    public function update(Request $request, Asset $asset)
    {
        $data = $this->validated($request, $asset);
        if ($asset->journal_id || $asset->disposed_at) {
            $data['asset'] = collect($data['asset'])->except(['acquisition_value', 'acquired_at', 'asset_account_id', 'category'])->all();
        }
        DB::transaction(function () use ($asset, $data) {
            $asset->update($data['asset']);
            $this->saveVehicle($asset, $data['vehicle']);
        });

        return redirect()->route('asset.show', $asset)->with('success', 'Aset diubah.');
    }

    public function document(Asset $asset, int $index)
    {
        $file = $asset->documents[$index] ?? null;
        abort_unless($file && Storage::disk('local')->exists($file['path']), 404);

        return Storage::disk('local')->response($file['path'], $file['name']);
    }

    /** Label QR 4×6 cm untuk banyak aset sekaligus; QR membuka detail aset (perlu login). */
    public function labels(Request $request)
    {
        $ids = $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer'], ['ids.required' => 'Centang aset yang akan dicetak labelnya.'])['ids'];
        $assets = Asset::whereIn('id', $ids)->orderBy('code')->get()->map(fn ($a) => [
            'asset' => $a, 'qr' => 'data:image/svg+xml;base64,' . base64_encode(QrCode::format('svg')->size(110)->margin(0)->generate(route('asset.show', $a))),
        ]);

        return Pdf::loadView('pdf.asset-labels', ['labels' => $assets, 'site' => Setting::current()])->setPaper('a4')->download('label-aset.pdf');
    }

    public function startUsage(Request $request, Asset $asset)
    {
        abort_unless($asset->isVehicle(), 404);
        $data = $request->validate(['purpose' => 'required|string|max:255', 'destination' => 'nullable|string|max:255', 'odometer_start' => 'nullable|integer|min:0']);
        $this->assets->startUsage($asset, $data, $request->user());

        return back()->with('success', 'Pemakaian dimulai.');
    }

    public function endUsage(Request $request, AssetUsage $usage)
    {
        abort_unless($request->user()->id === $usage->user_id || $request->user()->can('asset-update'), 403);
        $data = $request->validate([
            'odometer_end' => 'required|integer|min:0', 'fuel_cost' => 'nullable|numeric|min:0', 'fuel_liters' => 'nullable|numeric|min:0',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', 'note' => 'nullable|string|max:500',
        ]);
        $data['receipt'] = $request->hasFile('receipt') ? $request->file('receipt')->store('assets/receipts', 'local') : null;
        $this->assets->endUsage($usage, $data, $request->user());

        return back()->with('success', 'Pemakaian selesai' . ((float) ($data['fuel_cost'] ?? 0) > 0 ? '; voucher BBM masuk antrean Bendahara.' : '.'));
    }

    public function maintenance(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today', 'type' => ['required', Rule::in(array_keys(AssetMaintenance::TYPES))],
            'description' => 'required|string|max:255', 'cost' => 'nullable|numeric|min:0', 'vendor_party_id' => 'nullable|exists:parties,id',
            'odometer' => 'nullable|integer|min:0', 'next_due_at' => 'nullable|date|after:date',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);
        $data['cost'] ??= 0;
        $data['attachment'] = $request->hasFile('attachment') ? $request->file('attachment')->store('assets/maintenance', 'local') : null;
        $this->assets->maintenance($asset, $data, $request->user());

        return back()->with('success', 'Perawatan dicatat' . ((float) $data['cost'] > 0 ? '; voucher biaya masuk antrean Bendahara.' : '.'));
    }

    public function dispose(Request $request, Asset $asset)
    {
        $data = $request->validate(['disposed_at' => 'required|date|before_or_equal:today', 'disposal_note' => 'required|string|max:500',
            'proceeds' => 'nullable|numeric|min:0', 'coa_account_id' => 'nullable|exists:coa_accounts,id']);
        $journal = $this->assets->dispose($asset, $data['disposed_at'], $data['disposal_note'], (float) ($data['proceeds'] ?? 0), $data['coa_account_id'] ?? null, $request->user());

        return redirect()->route('journal.show', $journal)->with('success', 'Aset dilepas; voucher pelepasan masuk antrean Bendahara.');
    }

    private function formData(): array
    {
        return [
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'parties' => Party::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'accounts' => CoaAccount::postable()->whereIn('type', ['aset', 'beban'])->get(),
        ];
    }

    private function validated(Request $request, ?Asset $asset = null): array
    {
        $categories = array_keys(config('mantaaba.asset_categories'));
        $data = $request->validate([
            'name' => 'required|string|max:150', 'category' => ['required', Rule::in($categories)], 'location' => 'nullable|string|max:150',
            'condition' => ['required', Rule::in(array_keys(Asset::CONDITIONS))], 'pic_user_id' => 'nullable|exists:users,id',
            'acquired_at' => 'required|date', 'acquisition_value' => 'required|numeric|min:0|max:999999999999', 'source' => ['required', Rule::in(array_keys(Asset::SOURCES))],
            'useful_life_months' => 'nullable|integer|min:0|max:1200', 'residual_value' => 'nullable|numeric|min:0|lte:acquisition_value',
            'depreciation_start' => 'nullable|date', 'opening_accumulated' => 'nullable|numeric|min:0',
            'asset_account_id' => ['nullable', Rule::exists('coa_accounts', 'id')->where('type', 'aset')->where('is_header', false)],
            'notes' => 'nullable|string|max:2000', 'photo' => 'nullable|' . Upload::IMAGE_RULE,
            'documents' => 'nullable|array|max:10', 'documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
            'vehicle' => 'nullable|array', 'vehicle.plate_number' => 'nullable|string|max:20', 'vehicle.brand_type' => 'nullable|string|max:100',
            'vehicle.year' => 'nullable|integer|min:1950|max:2100', 'vehicle.color' => 'nullable|string|max:30',
            'vehicle.stnk_due_at' => 'nullable|date', 'vehicle.tax_due_at' => 'nullable|date', 'vehicle.kir_due_at' => 'nullable|date',
            'vehicle.last_odometer' => 'nullable|integer|min:0', 'vehicle.service_interval_km' => 'nullable|integer|min:0', 'vehicle.service_interval_days' => 'nullable|integer|min:0',
            'vehicle.last_service_at' => 'nullable|date', 'vehicle.last_service_km' => 'nullable|integer|min:0',
        ], ['residual_value.lte' => 'Nilai residu tidak boleh melebihi nilai perolehan.']);
        $defaults = AssetService::defaults($data['category']);
        $asset_data = collect($data)->except(['vehicle', 'photo', 'documents'])->all();
        $asset_data['asset_account_id'] ??= $defaults['asset_account_id'];
        $asset_data['accumulated_account_id'] = $defaults['accumulated_account_id'];
        $asset_data['expense_account_id'] = $defaults['expense_account_id'];
        $asset_data['useful_life_months'] = $data['category'] === 'tanah' ? null : ($data['useful_life_months'] ?? $defaults['useful_life_months']);
        $asset_data['residual_value'] ??= 0;
        $asset_data['opening_accumulated'] ??= 0;
        $asset_data['depreciation_start'] ??= $data['category'] === 'tanah' ? null : $data['acquired_at'];
        if ($request->hasFile('photo')) {
            $asset_data['photo'] = Upload::store($request->file('photo'), 'assets', $asset?->photo, 1280);
        }
        $documents = $asset?->documents ?? [];
        foreach ($request->file('documents', []) as $file) {
            $documents[] = ['path' => $file->storeAs('assets/documents', Str::random(24) . '.' . $file->extension(), 'local'), 'name' => $file->getClientOriginalName()];
        }
        $asset_data['documents'] = $documents ?: null;

        return ['asset' => $asset_data, 'vehicle' => $data['category'] === 'kendaraan' ? array_filter($data['vehicle'] ?? [], fn ($v) => $v !== null) : null];
    }

    private function saveVehicle(Asset $asset, ?array $vehicle): void
    {
        if ($vehicle !== null) {
            $asset->vehicle()->updateOrCreate(['asset_id' => $asset->id], $vehicle);
        }
    }
}
