<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberRequest;
use App\Models\Member;
use App\Models\Organization;
use App\Notifications\MemberActivation;
use App\Services\MemberRegistrar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

/** Data anggota (PRD 8.2). Hanya admin PD/SA yang mengubah; admin PC melihat PC-nya. */
class MembershipController extends Controller
{
    public const EXPORT_COLUMNS = ['nama', 'email', 'telepon', 'nomor_anggota', 'nomor_kip', 'jenis', 'kode_pc', 'sertifikasi', 'brevet', 'npwp', 'tanggal_bergabung'];

    public function __construct()
    {
        $this->middleware('permission:member-view')->only(['index', 'show', 'datatable', 'export', 'pplExport']);
        $this->middleware('permission:member-update')->only(['sendActivation']);
        $this->authorizeResource(Member::class, 'membership');
    }

    public function index(Request $request)
    {
        return view('dashboard.membership.index', [
            'organizations' => $this->organizationOptions($request),
            'selectedOrganization' => $request->query('organization_id'),
        ]);
    }

    /** Query anggota sesuai scope user + filter aktif (dipakai tabel & export). */
    private function filtered(Request $request): Builder
    {
        return Member::select('members.*')->with('user', 'organization', 'dues')
            ->visibleTo($request->user())
            ->when($request->filled('organization_id'), fn ($q) => $q->where('members.organization_id', $request->input('organization_id')))
            ->when($request->filled('membership_type'), fn ($q) => $q->where('membership_type', $request->input('membership_type')))
            ->when($request->filled('status'), fn ($q) => $q->where('members.status', $request->input('status')))
            ->when($request->filled('dues_status'), fn ($q) => $q->duesStatus($request->input('dues_status')));
    }

    public function datatable(Request $request)
    {
        return DataTables::eloquent($this->filtered($request))
            ->editColumn('membership_type', fn ($m) => Member::TYPES[$m->membership_type])
            ->editColumn('status', fn ($m) => Member::STATUSES[$m->status])
            ->addColumn('dues_status', fn ($m) => Member::DUES_STATUSES[$m->dues_status])
            ->addColumn('action', fn ($m) => view('components.dashboard.row-actions', [
                'show' => route('membership.show', $m),
                'edit' => $request->user()->can('update', $m) ? route('membership.edit', $m) : null,
                'delete' => $request->user()->can('delete', $m) ? route('membership.destroy', $m) : null,
                'confirm' => 'Data keanggotaan dihapus dan role anggota dicabut. Akun pengguna tetap ada.',
            ])->render())
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function create(Request $request)
    {
        return view('dashboard.membership.form', [
            'member' => new Member(['status' => 'aktif', 'organization_id' => $request->query('organization_id')]),
            'organizations' => $this->organizationOptions($request),
        ]);
    }

    public function store(MemberRequest $request, MemberRegistrar $registrar)
    {
        $data = $request->validated();
        $member = $registrar->register(
            array_intersect_key($data, array_flip(MemberRequest::userFields())),
            array_diff_key($data, array_flip(MemberRequest::userFields())),
            $request->boolean('send_activation'),
        );

        $note = $request->boolean('send_activation') ? ' Email aktivasi dikirim.' : '';

        return redirect()->route('membership.show', $member)->with('success', "Anggota {$member->user->name} berhasil ditambahkan.{$note}");
    }

    public function show(Member $membership)
    {
        $membership->load('user.participants.event', 'organization', 'dues.recorder');

        return view('dashboard.membership.show', [
            'member' => $membership,
            'defaultDues' => \App\Models\Setting::first()?->dues_amounts[$membership->membership_type] ?? null,
        ]);
    }

    public function edit(Request $request, Member $membership)
    {
        return view('dashboard.membership.form', [
            'member' => $membership->load('user'),
            'organizations' => $this->organizationOptions($request),
        ]);
    }

    public function update(MemberRequest $request, Member $membership)
    {
        $data = $request->validated();
        $data['brevet'] ??= null;

        DB::transaction(function () use ($membership, $data) {
            $membership->user->update(array_intersect_key($data, array_flip(MemberRequest::userFields())));
            // pindah PC & perubahan status tercatat di activity_logs lewat LogsActivity
            $membership->update(array_diff_key($data, array_flip(MemberRequest::userFields())));
        });

        return redirect()->route('membership.show', $membership)->with('success', 'Data anggota telah diubah.');
    }

    public function destroy(Member $membership)
    {
        $membership->delete();
        session()->flash('success', "Data keanggotaan {$membership->user->name} telah dihapus.");

        return response()->noContent();
    }

    public function sendActivation(Member $membership)
    {
        $this->authorize('update', $membership);
        $membership->user->notify(new MemberActivation);

        return back()->with('success', "Email aktivasi dikirim ke {$membership->user->email}.");
    }

    /** Export CSV sesuai filter aktif (PRD 8.2); kolom sama dengan template import + status. */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Member::class);
        $members = $this->filtered($request)->orderBy('member_number');

        return response()->streamDownload(function () use ($members) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [...self::EXPORT_COLUMNS, 'pc', 'status', 'status_iuran']);
            $members->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $m) {
                    fputcsv($out, [
                        $m->user->name, $m->user->email, $m->user->phone_number, $m->member_number, $m->kip_number,
                        $m->membership_type, $m->organization->code, $m->certification, implode(',', $m->brevet ?? []),
                        $m->npwp, $m->joined_at?->format('Y-m-d'), $m->organization->name, $m->status, $m->dues_status,
                    ]);
                }
            });
            fclose($out);
        }, 'anggota-akp2i-jabar-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /** Rekap jam PPL per anggota untuk satu tahun (PRD 8.9), sesuai scope. */
    public function pplExport(Request $request)
    {
        $this->authorize('viewAny', Member::class);
        $year = (int) ($request->query('tahun') ?: now()->year);
        $members = $this->filtered($request)->with('user')->orderBy('member_number');

        return response()->streamDownload(function () use ($members, $year) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['nomor_anggota', 'nama', 'pc', 'tahun', 'jam_ppl']);
            $members->chunk(500, function ($chunk) use ($out, $year) {
                foreach ($chunk as $m) {
                    fputcsv($out, [$m->member_number, $m->user->name, $m->organization->name, $year, $m->user->pplHoursByYear()[$year] ?? 0]);
                }
            });
            fclose($out);
        }, "rekap-ppl-{$year}.csv", ['Content-Type' => 'text/csv']);
    }

    private function organizationOptions(Request $request)
    {
        $ids = $request->user()->scopedOrganizationIds();

        return Organization::pc()->inActiveRegion()->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('name')->pluck('name', 'id');
    }
}
