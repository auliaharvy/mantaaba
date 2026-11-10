<?php

namespace App\Http\Controllers;

use App\Models\CoaAccount;
use App\Models\Donation;
use App\Models\Setting;
use App\Services\DonationService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/** Donasi di dashboard (PRD 9.15): verifikasi/tolak konfirmasi publik, catat donasi tunai/barang, rekap. */
class DonationController extends Controller
{
    public function __construct(private DonationService $service)
    {
        $this->middleware('permission:donation-view')->only(['index', 'datatable', 'show', 'proof']);
        $this->middleware('permission:donation-create')->only(['create', 'store']);
        $this->middleware('permission:donation-verify')->only(['verify', 'reject']);
    }

    public function index(Request $request)
    {
        $year = now()->year;
        $recap = Donation::where('status', 'terverifikasi')->whereYear('transferred_at', $year)->get()->groupBy('purpose')
            ->map(fn ($g) => ['count' => $g->count(), 'amount' => $g->sum(fn ($d) => Money::cents($d->amount))]);

        return view('dashboard.donation.index', ['recap' => $recap, 'year' => $year, 'waiting' => Donation::where('status', 'menunggu')->count()]);
    }

    public function datatable(Request $request)
    {
        return DataTables::eloquent(Donation::query()->with('journal:id,number,status')->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status'))))
            ->editColumn('donor_name', fn ($d) => e($d->displayName()) . ($d->is_anonymous ? ' <span class="text-muted fs-8">(' . e($d->donor_name) . ')</span>' : ''))
            ->editColumn('amount', fn ($d) => Money::format($d->amount))
            ->editColumn('transferred_at', fn ($d) => $d->transferred_at->translatedFormat('d M Y'))
            ->editColumn('purpose', fn ($d) => $d->purposeLabel())
            ->editColumn('status', fn ($d) => Donation::STATUSES[$d->status] . ($d->journal ? ' · ' . ($d->journal->number ?? 'voucher diajukan') : ''))
            ->addColumn('action', fn ($d) => view('components.dashboard.row-actions', ['show' => route('donation.show', $d), 'edit' => null, 'delete' => null, 'confirm' => null])->render())
            ->rawColumns(['donor_name', 'action'])->addIndexColumn()->make(true);
    }

    public function create()
    {
        return view('dashboard.donation.create', ['accounts' => $this->accounts()]);
    }

    /** Donasi diterima langsung (tunai/transfer/barang); bisa langsung diverifikasi. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(Donation::KINDS))],
            'donor_name' => 'required|string|max:100',
            'is_anonymous' => 'boolean',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'amount' => 'required|numeric|min:1|max:999999999999',
            'transferred_at' => 'required|date|before_or_equal:today',
            'coa_bank_account_id' => 'required|exists:coa_accounts,id',
            'purpose' => ['required', Rule::in(array_keys(Donation::PURPOSES))],
            'item_description' => 'nullable|required_if:kind,barang|string|max:255',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'message' => 'nullable|string|max:1000',
        ], ['item_description.required_if' => 'Jelaskan barang yang didonasikan.']);
        $data['is_anonymous'] = $request->boolean('is_anonymous');
        $data['proof'] = $request->hasFile('proof') ? $request->file('proof')->store('donations', 'local') : null;
        $donation = Donation::create($data);
        if ($request->boolean('verify_now') && $request->user()->can('donation-verify')) {
            $this->service->verify($donation, $request->user());

            return redirect()->route('donation.show', $donation)->with('success', 'Donasi dicatat dan voucher diajukan ke Bendahara.');
        }

        return redirect()->route('donation.show', $donation)->with('success', 'Donasi dicatat, menunggu verifikasi.');
    }

    public function show(Donation $donation)
    {
        $donation->load('journal', 'party', 'account');

        return view('dashboard.donation.show', ['donation' => $donation, 'accounts' => $this->accounts(),
            'receiptUrl' => $donation->journal?->status === 'terposting' ? $this->service->receiptUrl($donation) : null]);
    }

    public function verify(Request $request, Donation $donation)
    {
        $data = $request->validate(['coa_bank_account_id' => 'nullable|exists:coa_accounts,id']);
        $this->service->verify($donation, $request->user(), $data['coa_bank_account_id'] ?? null);

        return back()->with('success', 'Donasi terverifikasi. Voucher masuk antrean persetujuan Bendahara.');
    }

    public function reject(Request $request, Donation $donation)
    {
        $reason = $request->validate(['reason' => 'required|string|max:500'], ['reason.required' => 'Alasan penolakan wajib diisi.'])['reason'];
        $this->service->reject($donation, $request->user(), $reason);

        return back()->with('success', 'Konfirmasi donasi ditolak.');
    }

    public function proof(Donation $donation)
    {
        abort_unless($donation->proof && Storage::disk('local')->exists($donation->proof), 404);

        return Storage::disk('local')->response($donation->proof);
    }

    private function accounts()
    {
        return CoaAccount::postable()->where('type', 'aset')->where('is_receivable', false)->where('is_contra', false)->get();
    }
}
