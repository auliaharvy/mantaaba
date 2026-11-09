<?php

namespace App\Http\Controllers;

use App\Jobs\IssueEventCertificates;
use App\Models\Event;
use App\Support\Html;
use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/** Acara/Event (PRD 8.9). Admin PC hanya mengelola event PC-nya (scope PC). */
class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:agenda-view')->only(['index', 'show', 'datatable']);
        $this->authorizeResource(Event::class, 'event');
    }

    public function index()
    {
        return view('dashboard.event.index');
    }

    public function datatable(Request $request)
    {
        $events = Event::select('events.*')
            ->withCount(['participants as registrants_count' => fn ($q) => $q->whereIn('status', ['pending', 'verified'])])
            ->when($request->filled('status'), fn ($q) => $q->where('events.status', $request->input('status')));

        return DataTables::eloquent($events)
            ->editColumn('type', fn ($e) => Event::TYPES[$e->type])
            ->editColumn('status', fn ($e) => Event::STATUSES[$e->status])
            ->editColumn('starts_at', fn ($e) => $e->starts_at?->translatedFormat('d M Y H:i'))
            ->addColumn('seats', fn ($e) => $e->registrants_count . ($e->quota ? " / {$e->quota}" : ''))
            ->addColumn('action', fn ($e) => view('components.dashboard.row-actions', [
                'show' => route('event.show', $e),
                'edit' => $request->user()->can('update', $e) ? route('event.edit', $e) : null,
                'delete' => $request->user()->can('delete', $e) ? route('event.destroy', $e) : null,
                'confirm' => 'Event dihapus (arsip). Data peserta & sertifikat tetap tersimpan.',
            ])->render())
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        return view('dashboard.event.form', [
            'event' => new Event(['status' => 'draft', 'type' => 'lainnya', 'mode' => 'offline', 'audience' => 'publik', 'fee_member' => 0, 'fee_public' => 0]),
        ]);
    }

    public function store(Request $request)
    {
        $event = Event::create($this->validated($request));

        return redirect()->route('event.show', $event)->with('success', 'Event dibuat.');
    }

    public function show(Event $event)
    {
        $event->loadCount([
            'participants as pending_count' => fn ($q) => $q->where('status', 'pending'),
            'participants as verified_count' => fn ($q) => $q->where('status', 'verified'),
            'participants as attended_count' => fn ($q) => $q->where('status', 'verified')->whereNotNull('attended_at'),
        ]);
        $certificates = \App\Models\Certificate::whereHas('participant', fn ($q) => $q->where('event_id', $event->id))
            ->selectRaw('COUNT(*) as total, SUM(issued_at IS NOT NULL AND revoked_at IS NULL) as issued')->first();

        return view('dashboard.event.show', compact('event', 'certificates'));
    }

    public function edit(Event $event)
    {
        return view('dashboard.event.form', compact('event'));
    }

    public function update(Request $request, Event $event)
    {
        $event->update($this->validated($request, $event)); // slug tetap

        return redirect()->route('event.show', $event)->with('success', 'Event diubah.');
    }

    public function destroy(Event $event)
    {
        $event->delete(); // soft delete: peserta & sertifikat tetap bisa diverifikasi
        session()->flash('success', 'Event diarsipkan.');

        return response()->noContent();
    }

    /** Duplikasi event (PRD 8.9 fitur admin): salin pengaturan, tanpa peserta, status draf. */
    public function duplicate(Event $event)
    {
        $this->authorize('create', Event::class);
        $this->authorize('view', $event);
        $copy = $event->replicate(['slug', 'code', 'status']);
        $copy->title = $event->title . ' (salinan)';
        $copy->code = $this->generateCode($event->type);
        $copy->status = 'draft';
        $copy->save();

        return redirect()->route('event.edit', $copy)->with('success', 'Event disalin sebagai draf. Sesuaikan judul & jadwal.');
    }

    /** Terbitkan sertifikat untuk semua peserta hadir (PRD 8.9 alur 7) — lewat queue. */
    public function issueCertificates(Event $event)
    {
        $this->authorize('issue', \App\Models\Certificate::class);
        abort_unless($event->status === 'done', 422, 'Sertifikat hanya bisa diterbitkan setelah event berstatus Selesai.');
        IssueEventCertificates::dispatch($event);

        return back()->with('success', 'Penerbitan sertifikat sedang diproses di latar belakang. Peserta menerima email saat sertifikatnya siap.');
    }

    private function validated(Request $request, ?Event $event = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:300',
            'body' => 'required|string',
            'type' => ['required', Rule::in(array_keys(Event::TYPES))],
            'code' => ['nullable', 'alpha_dash', 'max:20', Rule::unique('events')->ignore($event)],
            'mode' => ['required', Rule::in(array_keys(Event::MODES))],
            'location' => 'nullable|string|max:255',
            'meeting_url' => 'nullable|url|max:255',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'registration_opens_at' => 'nullable|date',
            'registration_closes_at' => 'nullable|date|after_or_equal:registration_opens_at',
            'quota' => 'nullable|integer|min:1',
            'audience' => ['required', Rule::in(array_keys(Event::AUDIENCES))],
            'fee_member' => 'required|numeric|min:0',
            'fee_public' => 'required|numeric|min:0',
            'skp_hours' => 'nullable|numeric|min:0|max:999',
            'status' => ['required', Rule::in(array_keys(Event::STATUSES))],
            'thumbnail' => 'nullable|' . Upload::IMAGE_RULE,
            'certificate_template' => 'nullable|' . Upload::IMAGE_RULE,
            'signatories' => 'nullable|array|max:2',
            'signatories.*.name' => 'nullable|string|max:100',
            'signatories.*.title' => 'nullable|string|max:100',
            'signatures' => 'nullable|array|max:2',
            'signatures.*' => 'nullable|image|mimes:png,webp|max:1024',
        ]);
        $data['body'] = Html::clean($data['body']);
        $data['code'] = strtoupper($data['code'] ?? '') ?: ($event?->code ?? $this->generateCode($data['type']));

        foreach (['thumbnail' => 'event', 'certificate_template' => 'event/certificates'] as $field => $dir) {
            unset($data[$field]);
            if ($request->hasFile($field)) {
                $data[$field] = Upload::store($request->file($field), $dir, $event?->{$field}, $field === 'certificate_template' ? 3508 : 1920);
            }
        }

        // penandatangan maks. 2 (PRD 8.9): nama, jabatan, gambar tanda tangan (png transparan)
        $old = $event?->certificate_signatory ?? [];
        $data['certificate_signatory'] = collect([0, 1])->map(function ($i) use ($request, $old) {
            $signature = $old[$i]['signature'] ?? null;
            if ($request->hasFile("signatures.{$i}")) {
                $signature = Upload::store($request->file("signatures.{$i}"), 'event/signatures', $signature, 800);
            }

            return ['name' => $request->input("signatories.{$i}.name"), 'title' => $request->input("signatories.{$i}.title"), 'signature' => $signature];
        })->filter(fn ($s) => filled($s['name']))->values()->all();
        unset($data['signatories'], $data['signatures']);

        return $data;
    }

    private function generateCode(string $type): string
    {
        $prefix = strtoupper(Str::before(str_replace('brevet_', 'BRV', $type), '_')) ?: 'EV';
        do {
            $code = substr($prefix, 0, 4) . now()->format('ym') . strtoupper(Str::random(3));
        } while (Event::withTrashed()->where('code', $code)->exists());

        return $code;
    }
}
