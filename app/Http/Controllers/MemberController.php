<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Batch;
use App\Models\BusinessUnit;
use App\Models\Member;
use App\Models\MemberJourney;
use App\Models\User;
use App\Support\Upload;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

/** Data anggota (PRD 9.4). Kolom privat hanya untuk pemegang member-private. */
class MemberController extends Controller
{
    /** Kolom CSV import/export (urutan tetap). */
    public const CSV_COLUMNS = ['member_number', 'name', 'email', 'category', 'status', 'phase', 'batch', 'joined_at', ...Member::PRIVATE];

    public const DOCUMENT_TYPES = ['photo' => 'Pas foto', 'id_card' => 'KTP / KK', 'diploma' => 'Ijazah / SKL', 'sktm' => 'SKTM', 'parent_consent' => 'Persetujuan orang tua', 'lainnya' => 'Lainnya'];

    public function __construct()
    {
        $this->middleware('permission:member-view')->only(['index', 'datatable', 'verify']);
        $this->authorizeResource(Member::class, 'member', ['except' => ['index']]);
    }

    public function index()
    {
        return view('dashboard.member.index', ['batches' => Batch::orderByDesc('code')->pluck('code', 'id'), 'units' => BusinessUnit::orderBy('name')->pluck('name', 'id')]);
    }

    public function datatable(Request $request)
    {
        $private = $request->user()->can('member-private');
        $since = today()->subDays(30);
        $query = Member::query()->with('batch:id,code', 'activePlacement.businessUnit:id,name')
            ->select(['members.id', 'member_number', 'name', 'category', 'status', 'phase', 'batch_id', 'joined_at', ...($private ? ['district', 'phone'] : [])])
            ->withCount(['coachingNotes as heavy_count' => fn ($q) => $q->where('type', 'pelanggaran_berat')->where('date', '>=', $since),
                'coachingNotes as medium_count' => fn ($q) => $q->where('type', 'pelanggaran_sedang')->where('date', '>=', $since)])
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('phase'), fn ($q) => $q->where('phase', $request->input('phase')))
            ->when($request->filled('batch_id'), fn ($q) => $q->where('batch_id', $request->input('batch_id')))
            ->when($request->filled('unit'), fn ($q) => $q->whereHas('activePlacement', fn ($p) => $p->where('business_unit_id', $request->input('unit'))));

        return DataTables::eloquent($query)
            ->editColumn('name', fn ($m) => e($m->name) . ($m->heavy_count || $m->medium_count >= 3 ? ' <span class="badge badge-danger ms-1" title="Batas pelanggaran 30 hari">!</span>' : ''))
            ->editColumn('category', fn ($m) => Member::CATEGORIES[$m->category])
            ->editColumn('status', fn ($m) => Member::STATUSES[$m->status])
            ->editColumn('phase', fn ($m) => Member::PHASES[$m->phase] ?? '-')
            ->addColumn('batch_code', fn ($m) => $m->batch?->code ?? '-')
            ->addColumn('placement', fn ($m) => $m->activePlacement?->businessUnit?->name ?? '-')
            ->addColumn('action', fn ($m) => view('components.dashboard.row-actions', ['show' => route('member.show', $m), 'edit' => $request->user()->can('member-update') ? route('member.edit', $m) : null, 'delete' => null, 'confirm' => null])->render())
            ->removeColumn('heavy_count', 'medium_count', 'batch', 'active_placement')
            ->rawColumns(['name', 'action'])->addIndexColumn()->make(true);
    }

    public function create()
    {
        return view('dashboard.member.form', $this->formData(new Member(['category' => 'relawan', 'status' => 'aktif', 'joined_at' => today()])));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $member = DB::transaction(function () use ($data, $request) {
            $user = null;
            if ($email = $request->input('email')) {
                $user = User::where('email', $email)->doesntHave('member')->first();
                if (! $user) {
                    $user = User::create(['name' => $data['name'], 'email' => $email, 'phone_number' => $data['phone'] ?? null, 'password' => Hash::make(Str::random(40))]);
                    $user->assignRole('anggota');
                }
            }
            $member = Member::create($data + ['user_id' => $user?->id, 'member_number' => Member::nextNumber((int) (Batch::find($data['batch_id'] ?? 0)?->starts_at?->year ?? now()->year))]);
            if ($member->phase) {
                $member->startPhase($member->phase);
            }

            return $member;
        });

        return redirect()->route('member.show', $member)->with('success', "Anggota {$member->member_number} ditambahkan." . ($member->user ? ' Kirim tautan atur password dari menu Pengguna.' : ''));
    }

    public function show(Request $request, Member $member)
    {
        $member->load('user', 'batch', 'room', 'journeys.businessUnit', 'activePlacement.businessUnit', 'enrollments.skema', 'enrollments.result.activeCertificate', 'certificates');
        $tab = $request->query('tab', 'profil');
        $since = today()->subDays(30);

        return view('dashboard.member.show', [
            'member' => $member, 'tab' => $tab,
            'private' => $request->user()->can('member-private'),
            'notes' => $request->user()->can('coaching-view') ? $member->coachingNotes()->with('author')->get() : collect(),
            'summary' => $member->coachingNotes()->reorder()->where('date', '>=', $since)->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type'),
            'health' => $request->user()->can('health-view') ? $member->healthRecords()->with('creator')->get() : collect(),
            'participations' => $member->user?->participants()->with('event')->latest()->get() ?? collect(),
            'units' => BusinessUnit::orderBy('name')->pluck('name', 'id'),
            'duties' => $tab === 'piket' ? $member->duties()->with('area')->latest('date')->take(30)->get() : collect(),
            'dutyRate' => \App\Models\Duty::completionRate($member->id, today()->startOfMonth()->toDateString(), today()->toDateString()),
            'progress' => $member->user_id ? \App\Models\LessonProgress::where('user_id', $member->user_id)->whereNotNull('submitted_at')->with('lesson.module.skema')->latest('submitted_at')->get() : collect(),
        ]);
    }

    public function edit(Member $member)
    {
        return view('dashboard.member.form', $this->formData($member));
    }

    public function update(Request $request, Member $member)
    {
        $data = $this->validated($request, $member);
        $phaseChanged = isset($data['phase']) && $data['phase'] !== $member->phase;
        DB::transaction(function () use ($member, $data, $phaseChanged) {
            $member->update($data);
            $member->user?->update(['name' => $member->name]);
            if ($phaseChanged && $member->phase) {
                $member->startPhase($member->phase);
            }
        });

        return redirect()->route('member.show', $member)->with('success', 'Data anggota diubah.');
    }

    /** Ubah status dengan alasan wajib (PRD 9.4). */
    public function status(Request $request, Member $member)
    {
        $this->authorize('update', $member);
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Member::STATUSES))], 'reason' => 'required|string|max:500']);
        $member->changeStatus($data['status'], $data['reason']);

        return back()->with('success', 'Status anggota menjadi ' . Member::STATUSES[$data['status']] . '.');
    }

    public function storeJourney(Request $request, Member $member)
    {
        $this->authorize('update', $member);
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(MemberJourney::TYPES))],
            'title' => 'required|string|max:255',
            'business_unit_id' => 'nullable|required_if:type,penempatan|exists:business_units,id',
            'role_level' => ['nullable', Rule::in(array_keys(MemberJourney::ROLE_LEVELS))],
            'started_at' => 'required|date',
            'ended_at' => 'nullable|date|after_or_equal:started_at',
            'meta.nim' => 'nullable|string|max:30',
            'meta.semester' => 'nullable|integer|min:1|max:20',
            'meta.ipk' => 'nullable|numeric|min:0|max:4',
            'notes' => 'nullable|string|max:1000',
        ]);
        $data['meta'] = array_filter($data['meta'] ?? [], fn ($v) => $v !== null) ?: null;
        $member->journeys()->create($data);

        return redirect()->route('member.show', [$member, 'tab' => 'perjalanan'])->with('success', 'Perjalanan ditambahkan.');
    }

    public function endJourney(Member $member, MemberJourney $journey)
    {
        $this->authorize('update', $member);
        abort_unless($journey->member_id === $member->id, 404);
        $journey->update(['ended_at' => $journey->ended_at ?? today()]);

        return redirect()->route('member.show', [$member, 'tab' => 'perjalanan'])->with('success', 'Perjalanan ditutup.');
    }

    public function destroyJourney(Member $member, MemberJourney $journey)
    {
        $this->authorize('update', $member);
        abort_unless($journey->member_id === $member->id, 404);
        $journey->delete();

        return redirect()->route('member.show', [$member, 'tab' => 'perjalanan'])->with('success', 'Perjalanan dihapus.');
    }

    /** Berkas anggota (KTP, ijazah, pakta) — privat. */
    public function document(Request $request, Member $member, string $key)
    {
        abort_unless($request->user()->can('member-private'), 403);
        $path = $key === 'pakta' ? $member->integrity_pact_file : ($member->documents[$key] ?? null);
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, "{$member->member_number}-{$key}." . pathinfo($path, PATHINFO_EXTENSION));
    }

    public function uploadDocument(Request $request, Member $member)
    {
        $this->authorize('update', $member);
        abort_unless($request->user()->can('member-private'), 403);
        $data = $request->validate(['key' => ['required', Rule::in([...array_keys(self::DOCUMENT_TYPES), 'pakta'])], 'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096']);
        $path = $request->file('file')->storeAs("members/{$member->member_number}", $data['key'] . '-' . Str::random(8) . '.' . $request->file('file')->extension(), 'local');
        if ($data['key'] === 'pakta') {
            Storage::disk('local')->delete((string) $member->integrity_pact_file);
            $member->update(['integrity_pact_file' => $path]);
        } else {
            Storage::disk('local')->delete((string) ($member->documents[$data['key']] ?? ''));
            $member->update(['documents' => [...($member->documents ?? []), $data['key'] => $path]]);
        }

        return redirect()->route('member.show', [$member, 'tab' => 'berkas'])->with('success', 'Berkas diunggah.');
    }

    /** Kartu anggota PDF ber-QR (QR ke halaman verifikasi internal). */
    public function card(Member $member)
    {
        $this->authorize('view', $member);
        $url = route('member.verify', $member->member_number);
        $photo = $member->photo && Storage::disk('public')->exists($member->photo)
            ? 'data:' . Storage::disk('public')->mimeType($member->photo) . ';base64,' . base64_encode(Storage::disk('public')->get($member->photo)) : null;

        return Pdf::loadView('pdf.member-card', [
            'member' => $member->load('batch'), 'photo' => $photo, 'site' => \App\Models\Setting::current(),
            'qr' => 'data:image/svg+xml;base64,' . base64_encode(QrCode::format('svg')->size(120)->margin(0)->generate($url)),
        ])->setPaper([0, 0, 242.65, 153.07])->download("kartu-{$member->member_number}.pdf");
    }

    public function verify(string $number)
    {
        $member = Member::where('member_number', $number)->with('batch')->firstOrFail();

        return view('dashboard.member.verify', compact('member'));
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('member-export'), 403);
        $columns = $request->user()->can('member-private') ? self::CSV_COLUMNS : array_values(array_diff(self::CSV_COLUMNS, Member::PRIVATE));

        return response()->streamDownload(function () use ($columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            Member::with('user:id,email', 'batch:id,code')->orderBy('member_number')->each(function (Member $m) use ($out, $columns) {
                fputcsv($out, array_map(fn ($c) => match ($c) {
                    'email' => $m->user?->email, 'batch' => $m->batch?->code,
                    default => $m->{$c} instanceof \DateTimeInterface ? $m->{$c}->format('Y-m-d') : $m->{$c},
                }, $columns));
            });
            fclose($out);
        }, 'anggota-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /** Import CSV untuk data awal: baris valid dibuat, baris bermasalah dilaporkan. */
    public function import(Request $request)
    {
        $this->authorize('create', Member::class);
        abort_unless($request->user()->can('member-private'), 403);
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map(fn ($h) => Str::snake(trim(preg_replace('/^\xEF\xBB\xBF/', '', $h))), fgetcsv($handle) ?: []);
        $created = 0;
        $errors = [];
        $line = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $record = array_map(fn ($v) => ($v = trim((string) $v)) === '' ? null : $v, array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), null)));
            $validator = Validator::make($record, [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:users,email',
                'category' => ['required', Rule::in(array_keys(Member::CATEGORIES))],
                'status' => ['nullable', Rule::in(array_keys(Member::STATUSES))],
                'phase' => ['nullable', Rule::in(array_keys(Member::PHASES))],
                'batch' => 'nullable|exists:batches,code',
                'joined_at' => 'nullable|date',
                'nik' => 'nullable|digits:16',
                'birth_date' => 'nullable|date',
                'gender' => 'nullable|in:L,P',
            ]);
            if ($validator->fails()) {
                $errors[] = "Baris {$line}: " . implode(' ', $validator->errors()->all());
                continue;
            }
            DB::transaction(function () use ($record) {
                $batch = $record['batch'] ? Batch::where('code', $record['batch'])->first() : null;
                $user = null;
                if ($record['email']) {
                    $user = User::create(['name' => $record['name'], 'email' => $record['email'], 'phone_number' => $record['phone'] ?? null, 'password' => Hash::make(Str::random(40))]);
                    $user->assignRole('anggota');
                }
                Member::create(collect($record)->only(['name', 'category', 'phase', 'joined_at', ...Member::PRIVATE])->all() + [
                    'status' => $record['status'] ?? 'aktif', 'batch_id' => $batch?->id, 'user_id' => $user?->id,
                    'member_number' => Member::nextNumber((int) ($record['joined_at'] ? substr($record['joined_at'], 0, 4) : now()->year)),
                    'integrity_pact_signed_at' => now(), // data lama: pakta sudah ditandatangani di luar sistem
                ]);
            });
            $created++;
        }
        fclose($handle);

        return redirect()->route('member.index')->with($errors ? 'warning' : 'success', "{$created} anggota diimpor." . ($errors ? ' ' . count($errors) . ' baris dilewati.' : ''))->with('import_errors', $errors);
    }

    private function formData(Member $member): array
    {
        return ['member' => $member, 'batches' => Batch::orderByDesc('code')->pluck('code', 'id'), 'private' => auth()->user()->can('member-private')];
    }

    private function validated(Request $request, ?Member $member = null): array
    {
        $private = $request->user()->can('member-private');
        $rules = [
            'name' => 'required|string|max:255',
            'category' => ['required', Rule::in(array_keys(Member::CATEGORIES))],
            'phase' => ['nullable', Rule::in(array_keys(Member::PHASES))],
            'batch_id' => 'nullable|exists:batches,id',
            'joined_at' => 'nullable|date',
            'show_on_site' => 'boolean',
            'notes' => 'nullable|string|max:2000',
            'photo' => 'nullable|' . Upload::IMAGE_RULE,
        ];
        if (! $member) {
            $rules += ['status' => ['required', Rule::in(array_keys(Member::STATUSES))], 'email' => 'nullable|email|max:255'];
        }
        if ($private) {
            $rules += [
                'nik' => 'nullable|digits:16', 'birth_place' => 'nullable|string|max:100', 'birth_date' => 'nullable|date|before:today',
                'gender' => 'nullable|in:L,P', 'address' => 'nullable|string|max:255', 'village' => 'nullable|string|max:100', 'district' => 'nullable|string|max:100',
                'regency' => 'nullable|string|max:100', 'phone' => 'nullable|string|max:30', 'father_name' => 'nullable|string|max:100', 'mother_name' => 'nullable|string|max:100',
                'guardian_name' => 'nullable|string|max:100', 'guardian_phone' => 'nullable|string|max:30',
                'family_status' => ['nullable', Rule::in(array_keys(Applicant::FAMILY_STATUSES))], 'economic_status' => ['nullable', Rule::in(array_keys(Applicant::ECONOMIC_STATUSES))],
            ];
        }
        $data = $request->validate($rules);
        unset($data['email'], $data['photo']);
        $data['show_on_site'] = $request->boolean('show_on_site');
        if ($request->hasFile('photo')) {
            $data['photo'] = Upload::store($request->file('photo'), 'members', $member?->photo, 800);
        }

        return $data;
    }
}
