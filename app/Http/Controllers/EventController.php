<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Event;
use App\Models\Member;
use App\Models\Participant;
use App\Models\Skema;
use App\Support\Html;
use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/** Agenda (PRD 9.8): daftar, kalender, CRUD. Agenda anggota/angkatan memakai peserta otomatis. */
class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:agenda-view')->only(['index', 'show', 'datatable', 'calendar']);
        $this->authorizeResource(Event::class, 'event');
    }

    public function index()
    {
        return view('dashboard.event.index');
    }

    public function datatable(Request $request)
    {
        $events = Event::select('events.*')->with('batch:id,code')
            ->withCount(['participants as registrants_count' => fn ($q) => $q->where('status', '!=', 'batal'), 'participants as present_count' => fn ($q) => $q->where('status', 'hadir')])
            ->when($request->filled('status'), fn ($q) => $q->where('events.status', $request->input('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('events.type', $request->input('type')));

        return DataTables::eloquent($events)
            ->editColumn('type', fn ($e) => Event::TYPES[$e->type])
            ->editColumn('status', fn ($e) => Event::STATUSES[$e->status])
            ->editColumn('audience', fn ($e) => Event::AUDIENCES[$e->audience] . ($e->batch ? ' ' . $e->batch->code : ''))
            ->editColumn('starts_at', fn ($e) => $e->starts_at->translatedFormat('d M Y H:i'))
            ->addColumn('seats', fn ($e) => "{$e->present_count} hadir / {$e->registrants_count}" . ($e->quota ? " (kuota {$e->quota})" : ''))
            ->addColumn('action', fn ($e) => view('components.dashboard.row-actions', [
                'show' => route('event.show', $e),
                'edit' => $request->user()->can('update', $e) ? route('event.edit', $e) : null,
                'delete' => $request->user()->can('delete', $e) ? route('event.destroy', $e) : null,
                'confirm' => 'Agenda diarsipkan. Data peserta & kehadiran tetap tersimpan.',
            ])->render())
            ->rawColumns(['action'])->addIndexColumn()->make(true);
    }

    /** Sumber data FullCalendar (PRD 9.8). */
    public function calendar(Request $request)
    {
        $data = $request->validate(['start' => 'required|date', 'end' => 'required|date']);
        $colors = ['kajian_tajug' => '#1E4D3A', 'sedulur_selawase' => '#A84B24', 'kelas' => '#2563eb', 'kegiatan_umum' => '#C9A04A', 'open_house' => '#16a34a', 'lainnya' => '#6b7280'];

        return Event::whereBetween('starts_at', [$data['start'], $data['end']])->get()->map(fn (Event $e) => [
            'id' => $e->id, 'title' => $e->title, 'start' => $e->starts_at->toIso8601String(), 'end' => $e->ends_at->toIso8601String(),
            'url' => route('event.show', $e), 'color' => $e->status === 'draft' ? '#a1a5b7' : $colors[$e->type],
        ]);
    }

    public function create()
    {
        return view('dashboard.event.form', $this->formData(new Event(['status' => 'draft', 'type' => 'kajian_tajug', 'mode' => 'offline', 'audience' => 'publik'])));
    }

    public function store(Request $request)
    {
        $event = Event::create($this->validated($request));
        $added = $event->syncParticipants();

        return redirect()->route('event.show', $event)->with('success', 'Agenda dibuat.' . ($added ? " {$added} anggota otomatis menjadi peserta." : ''));
    }

    public function show(Event $event)
    {
        $event->load('batch', 'skema')->loadCount([
            'participants as registered_count' => fn ($q) => $q->where('status', '!=', 'batal'),
            'participants as present_count' => fn ($q) => $q->where('status', 'hadir'),
        ]);

        return view('dashboard.event.show', compact('event'));
    }

    public function edit(Event $event)
    {
        return view('dashboard.event.form', $this->formData($event));
    }

    public function update(Request $request, Event $event)
    {
        $event->update($this->validated($request, $event)); // slug tetap
        $added = $event->syncParticipants();

        return redirect()->route('event.show', $event)->with('success', 'Agenda diubah.' . ($added ? " {$added} peserta baru ditambahkan otomatis." : ''));
    }

    public function destroy(Event $event)
    {
        $event->delete(); // soft delete
        session()->flash('success', 'Agenda diarsipkan.');

        return response()->noContent();
    }

    /** Duplikasi agenda: salin pengaturan, tanpa peserta, status draf. */
    public function duplicate(Event $event)
    {
        $this->authorize('create', Event::class);
        $copy = $event->replicate(['slug', 'status']);
        $copy->title = $event->title . ' (salinan)';
        $copy->status = 'draft';
        $copy->save();

        return redirect()->route('event.edit', $copy)->with('success', 'Agenda disalin sebagai draf. Sesuaikan judul & jadwal.');
    }

    /**
     * Laporan Sedulur Selawase (PRD 9.8, Grand Design 3): alumni & anggota Fase II yang tidak hadir di agenda
     * Sedulur Selawase dalam 3 bulan terakhir. ?export=1 → CSV.
     */
    public function selawase(Request $request)
    {
        abort_unless($request->user()->can('agenda-view'), 403);
        $since = now()->subMonths(3);
        $attendedUserIds = Participant::where('status', 'hadir')
            ->whereHas('event', fn ($q) => $q->where('type', 'sedulur_selawase')->where('starts_at', '>=', $since))->pluck('user_id')->filter();
        $lastAttendance = Participant::where('participants.status', 'hadir')->join('events', 'events.id', '=', 'participants.event_id')
            ->where('events.type', 'sedulur_selawase')->groupBy('participants.user_id')->selectRaw('participants.user_id, max(events.starts_at) as last_at')->pluck('last_at', 'user_id');
        $members = Member::with('batch:id,code')->whereIn('status', ['aktif', 'lulus'])
            ->where(fn ($q) => $q->where('category', 'alumni')->orWhereIn('phase', ['magang', 'bekerja', 'ownership', 'alumni']))
            ->where(fn ($q) => $q->whereNull('user_id')->orWhereNotIn('user_id', $attendedUserIds))
            ->orderBy('name')->get()->each(fn ($m) => $m->last_selawase = $m->user_id ? ($lastAttendance[$m->user_id] ?? null) : null);

        if ($request->boolean('export')) {
            return response()->streamDownload(function () use ($members) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['nomor', 'nama', 'kategori', 'fase', 'angkatan', 'telepon', 'terakhir_hadir']);
                foreach ($members as $m) {
                    fputcsv($out, [$m->member_number, $m->name, $m->category, $m->phase, $m->batch?->code, $m->phone, $m->last_selawase]);
                }
                fclose($out);
            }, 'sedulur-selawase-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
        }

        return view('dashboard.event.selawase', compact('members', 'since'));
    }

    private function formData(Event $event): array
    {
        return ['event' => $event, 'batches' => Batch::orderByDesc('code')->pluck('code', 'id'), 'skemas' => Skema::ordered()->get()->mapWithKeys(fn ($s) => [$s->id => "{$s->code} · {$s->name}"])];
    }

    private function validated(Request $request, ?Event $event = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:300',
            'body' => 'nullable|string',
            'type' => ['required', Rule::in(array_keys(Event::TYPES))],
            'mode' => ['required', Rule::in(array_keys(Event::MODES))],
            'location' => 'nullable|string|max:255',
            'meeting_url' => 'nullable|url|max:255',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'registration_opens_at' => 'nullable|date',
            'registration_closes_at' => 'nullable|date|after_or_equal:registration_opens_at',
            'quota' => 'nullable|integer|min:1',
            'audience' => ['required', Rule::in(array_keys(Event::AUDIENCES))],
            'batch_id' => 'nullable|required_if:audience,angkatan|exists:batches,id',
            'skema_id' => 'nullable|exists:skemas,id',
            'status' => ['required', Rule::in(array_keys(Event::STATUSES))],
            'thumbnail' => 'nullable|' . Upload::IMAGE_RULE,
        ], ['batch_id.required_if' => 'Pilih angkatan untuk agenda satu angkatan.']);
        $data['body'] = Html::clean($data['body'] ?? null);
        $data['batch_id'] = $data['audience'] === 'angkatan' ? $data['batch_id'] : null;
        $data['skema_id'] = $data['type'] === 'kelas' ? ($data['skema_id'] ?? null) : null;
        unset($data['thumbnail']);
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = Upload::store($request->file('thumbnail'), 'event', $event?->thumbnail);
        }

        return $data;
    }
}
