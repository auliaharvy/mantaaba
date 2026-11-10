<?php

namespace App\Http\Controllers;

use App\Models\CoaAccount;
use App\Models\CostCenter;
use App\Models\HealthRecord;
use App\Models\Journal;
use App\Models\Party;
use App\Models\Setting;
use App\Services\JournalPoster;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

/** Voucher (PRD 9.13.2): draf, ajukan, antrean persetujuan, posting/tolak, balik, cetak. */
class JournalController extends Controller
{
    public function __construct(private JournalPoster $poster)
    {
        $this->middleware('permission:journal-view')->only(['index', 'datatable', 'show', 'attachment', 'pdf']);
        $this->middleware('permission:journal-approve')->only(['queue', 'post', 'postMany', 'reject', 'reverse']);
    }

    public function index(Request $request)
    {
        $counts = Journal::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('dashboard.journal.index', ['counts' => $counts, 'status' => $request->query('status', '')]);
    }

    public function datatable(Request $request)
    {
        $query = Journal::query()->select('journals.*')->with('creator:id,name')->withSum('lines', 'debit')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('voucher_type', $request->input('type')))
            ->when($request->filled('from'), fn ($q) => $q->where('date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('date', '<=', $request->input('to')));

        return DataTables::eloquent($query)
            ->editColumn('number', fn ($j) => $j->displayNumber())
            ->editColumn('date', fn ($j) => $j->date->translatedFormat('d M Y'))
            ->addColumn('amount', fn ($j) => Money::format($j->lines_sum_debit))
            ->editColumn('status', fn ($j) => '<span class="badge badge-' . Journal::STATUS_COLORS[$j->status] . '">' . Journal::STATUSES[$j->status] . '</span>' . ($j->is_system ? ' <span class="badge badge-light fs-8">sistem</span>' : ''))
            ->addColumn('action', fn ($j) => view('components.dashboard.row-actions', ['show' => route('journal.show', $j), 'edit' => $request->user()->can('update', $j) ? route('journal.edit', $j) : null, 'delete' => null, 'confirm' => null])->render())
            ->rawColumns(['status', 'action'])->addIndexColumn()->make(true);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Journal::class);
        $template = config('mantaaba.voucher_templates.' . $request->query('template'));
        $type = $template['type'] ?? (array_key_exists($request->query('type'), Journal::TYPES) ? $request->query('type') : 'CPV');
        $journal = new Journal(['voucher_type' => $type, 'date' => today(), 'description' => $template['description'] ?? null]);
        $lines = $template ? collect($template['lines'])->map(fn ($l) => ['coa_account_id' => $l[0] ? CoaAccount::where('code', $l[0])->value('id') : null,
            'cost_center_id' => $l[2] ? CostCenter::byCode($l[2])?->id : null, 'side' => $l[1]])->all() : [[], []];

        return view('dashboard.journal.form', $this->formData($journal, $lines));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Journal::class);
        [$header, $lines] = $this->validated($request);
        $journal = DB::transaction(function () use ($request, $header, $lines) {
            $journal = $this->poster->saveDraft($header, $lines, $request->user());
            $this->syncAttachments($request, $journal);

            return $journal;
        });

        return $this->afterSave($request, $journal, 'Draf voucher disimpan.');
    }

    public function show(Journal $journal)
    {
        $journal->load('lines.account', 'lines.costCenter', 'lines.party', 'party', 'costCenter', 'creator', 'submitter', 'approver', 'reversal', 'reversalOf', 'period');

        return view('dashboard.journal.show', compact('journal'));
    }

    public function edit(Journal $journal)
    {
        $this->ensureEditable($journal);
        $this->authorize('update', $journal);
        $journal->load('lines');

        return view('dashboard.journal.form', $this->formData($journal, $journal->lines->map(fn ($l) => $l->only(['coa_account_id', 'debit', 'credit', 'cost_center_id', 'party_id', 'memo']))->all()));
    }

    public function update(Request $request, Journal $journal)
    {
        $this->ensureEditable($journal);
        $this->authorize('update', $journal);
        [$header, $lines] = $this->validated($request);
        DB::transaction(function () use ($request, $journal, $header, $lines) {
            $this->poster->saveDraft($header, $lines, $request->user(), $journal);
            $this->syncAttachments($request, $journal);
        });

        return $this->afterSave($request, $journal->refresh(), 'Voucher diubah.');
    }

    public function destroy(Journal $journal)
    {
        $this->ensureEditable($journal);
        $this->authorize('delete', $journal);
        Storage::disk('local')->deleteDirectory("journals/{$journal->id}");
        $journal->delete();
        session()->flash('success', 'Draf voucher dihapus.');

        return response()->noContent();
    }

    public function submit(Request $request, Journal $journal)
    {
        abort_unless($request->user()->can('journal-submit'), 403);
        $this->poster->submit($journal, $request->user(), $request->boolean('confirm_budget'));

        return redirect()->route('journal.show', $journal)->with('success', 'Voucher diajukan untuk persetujuan.');
    }

    /** Antrean persetujuan Bendahara: voucher diajukan + pratinjau lampiran. */
    public function queue(Request $request)
    {
        $journals = Journal::where('status', 'diajukan')->with('lines.account', 'lines.costCenter', 'creator', 'submitter')->orderBy('date')->get();

        return view('dashboard.journal.queue', compact('journals'));
    }

    public function post(Request $request, Journal $journal)
    {
        $this->poster->post($journal, $request->user());

        return back()->with('success', "Voucher {$journal->fresh()->number} terposting.");
    }

    /** Posting massal; voucher yang gagal dilaporkan tanpa membatalkan yang lain. */
    public function postMany(Request $request)
    {
        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];
        $posted = 0;
        $failed = [];
        foreach (Journal::whereIn('id', $ids)->where('status', 'diajukan')->get() as $journal) {
            try {
                $this->poster->post($journal, $request->user());
                $posted++;
            } catch (ValidationException $e) {
                $failed[] = "{$journal->description}: " . collect($e->errors())->flatten()->first();
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                $failed[] = "{$journal->description}: {$e->getMessage()}";
            }
        }
        $redirect = back()->with('success', "{$posted} voucher terposting.");

        return $failed ? $redirect->with('warning', 'Tidak terposting — ' . implode(' · ', $failed)) : $redirect;
    }

    public function reject(Request $request, Journal $journal)
    {
        $reason = $request->validate(['reason' => 'required|string|max:500'], ['reason.required' => 'Alasan penolakan wajib diisi.'])['reason'];
        $this->poster->reject($journal, $request->user(), $reason);

        return back()->with('success', 'Voucher ditolak dan dikembalikan ke pembuat.');
    }

    public function reverse(Request $request, Journal $journal)
    {
        $data = $request->validate(['date' => 'nullable|date']);
        $reversal = $this->poster->reverse($journal, $request->user(), $data['date'] ?? null);

        return redirect()->route('journal.show', $reversal)->with('success', "Voucher {$journal->number} dibalik dengan {$reversal->number}. Buat voucher koreksi yang benar bila perlu.");
    }

    public function attachment(Journal $journal, int $index)
    {
        $file = $journal->attachments[$index] ?? null;
        abort_unless($file && Storage::disk('local')->exists($file['path']), 404);

        return Storage::disk('local')->response($file['path'], $file['name']);
    }

    /** Cetak voucher PDF untuk arsip fisik (Whitepaper: filing & bundling). */
    public function pdf(Journal $journal)
    {
        $journal->load('lines.account', 'lines.costCenter', 'lines.party', 'party', 'creator', 'submitter', 'approver');

        return Pdf::loadView('pdf.voucher', ['journal' => $journal, 'site' => Setting::current()])->setPaper('a4')
            ->download('voucher-' . str_replace('/', '-', $journal->displayNumber()) . '.pdf');
    }

    /** PRD 9.16: biaya kesehatan siswa → draf CPV terisi (pilih kas/bank & lampirkan kuitansi). */
    public function fromHealth(Request $request, HealthRecord $health)
    {
        $this->authorize('create', Journal::class);
        abort_unless($health->cost > 0, 422, 'Catatan ini tidak memiliki biaya.');
        if ($existing = Journal::where('source_type', $health->getMorphClass())->where('source_id', $health->id)->whereNotIn('status', ['dibalik'])->first()) {
            return redirect()->route('journal.show', $existing)->with('warning', 'Voucher untuk catatan kesehatan ini sudah ada.');
        }
        $rule = config('mantaaba.posting_rules.health');
        $center = CostCenter::byCode($rule['cost_center'])?->id;
        $journal = $this->poster->saveDraft([
            'voucher_type' => 'CPV', 'date' => $health->date->toDateString(), 'cost_center_id' => $center,
            'description' => "Biaya kesehatan {$health->member->name}: {$health->complaint}", 'reference_no' => $health->facility,
            'source_type' => $health->getMorphClass(), 'source_id' => $health->id,
        ], [
            ['coa_account_id' => \App\Support\PostingRules::account('health.debit')->id, 'debit' => $health->cost, 'cost_center_id' => $center],
            ['coa_account_id' => \App\Support\PostingRules::account('health.credit')->id, 'credit' => $health->cost],
        ], $request->user());

        return redirect()->route('journal.edit', $journal)->with('success', 'Draf voucher biaya kesehatan dibuat. Periksa akun kas/bank lalu lampirkan kuitansi.');
    }

    /** Aturan 10: berlaku juga untuk Super Admin (Gate::before tidak boleh melewati). */
    private function ensureEditable(Journal $journal): void
    {
        abort_unless($journal->isEditable() && ! $journal->is_system, 403, 'Voucher yang sudah diajukan/terposting atau voucher sistem tidak bisa diubah/dihapus. Koreksi lewat Balik.');
    }

    private function afterSave(Request $request, Journal $journal, string $message)
    {
        if ($request->input('action') === 'submit') {
            abort_unless($request->user()->can('journal-submit'), 403);
            try {
                $this->poster->submit($journal, $request->user(), $request->boolean('confirm_budget'));
            } catch (\Illuminate\Validation\ValidationException $e) {
                throw_if($request->expectsJson(), $e);
                // draf sudah tersimpan: kembali ke halaman voucher (bukan form) agar tidak tercipta draf ganda
                return redirect()->route('journal.show', $journal)->withErrors($e->errors())->with('success', 'Draf disimpan, tetapi belum bisa diajukan.');
            }
            $message = 'Voucher disimpan dan diajukan.';
        }

        return redirect()->route('journal.show', $journal)->with('success', $message);
    }

    private function formData(Journal $journal, array $lines): array
    {
        return [
            'journal' => $journal, 'lines' => $lines,
            'accounts' => CoaAccount::postable()->get(['id', 'code', 'name', 'type', 'is_cash', 'is_bank', 'is_receivable', 'is_payable']),
            'centers' => CostCenter::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'parties' => Party::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'types' => collect(Journal::TYPES)->when(! auth()->user()->can('journal-approve'), fn ($t) => $t->except('AJV')),
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'voucher_type' => ['required', Rule::in(array_keys(Journal::TYPES))],
            'date' => 'required|date',
            'description' => 'required|string|max:500',
            'party_id' => 'nullable|exists:parties,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'reference_no' => 'nullable|string|max:100',
            'lines' => 'required|array|max:50',
            'lines.*.coa_account_id' => 'nullable|exists:coa_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0|max:999999999999',
            'lines.*.credit' => 'nullable|numeric|min:0|max:999999999999',
            'lines.*.cost_center_id' => 'nullable|exists:cost_centers,id',
            'lines.*.party_id' => 'nullable|exists:parties,id',
            'lines.*.memo' => 'nullable|string|max:255',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
            'remove_attachments' => 'nullable|array',
        ], ['attachments.*.mimes' => 'Lampiran harus jpg, png, atau pdf.', 'attachments.*.max' => 'Lampiran maksimal 5 MB per file.']);
        // baris kosong (tanpa akun & nilai) diabaikan
        $lines = array_values(array_filter($data['lines'], fn ($l) => filled($l['coa_account_id'] ?? null) || (float) ($l['debit'] ?? 0) || (float) ($l['credit'] ?? 0)));
        foreach ($lines as $i => $line) {
            if (blank($line['coa_account_id'] ?? null)) {
                throw ValidationException::withMessages(['lines' => 'Baris ' . ($i + 1) . ': pilih akun.']);
            }
            // pihak di header berlaku untuk baris piutang/utang yang belum diisi
            $lines[$i]['party_id'] = $line['party_id'] ?? $data['party_id'] ?? null;
        }
        $header = collect($data)->only(['voucher_type', 'date', 'description', 'party_id', 'cost_center_id', 'reference_no'])->all();

        return [$header, $lines];
    }

    private function syncAttachments(Request $request, Journal $journal): void
    {
        $files = collect($journal->attachments ?? []);
        foreach ((array) $request->input('remove_attachments', []) as $index) {
            if ($file = $files->get((int) $index)) {
                Storage::disk('local')->delete($file['path']);
                $files->forget((int) $index);
            }
        }
        foreach ($request->file('attachments', []) as $upload) {
            $files->push(['path' => $upload->store("journals/{$journal->id}", 'local'), 'name' => $upload->getClientOriginalName()]);
        }
        $journal->forceFill(['attachments' => $files->values()->all() ?: null])->save();
    }
}
