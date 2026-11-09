<?php

namespace App\Http\Controllers;

use App\Models\BoardPeriod;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/** Kepengurusan PD & PC per periode (PRD 8.4). */
class BoardPeriodController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:board-view')->only(['index', 'show', 'datatable']);
        $this->authorizeResource(BoardPeriod::class, 'board');
    }

    public function index(Request $request)
    {
        return view('dashboard.board.index', [
            'organizations' => $this->organizationOptions($request),
            'selectedOrganization' => $request->query('organization_id'),
        ]);
    }

    public function datatable(Request $request)
    {
        $ids = $request->user()->scopedOrganizationIds();
        $periods = BoardPeriod::select('board_periods.*')->with('organization')->withCount('boardMembers')
            ->when($ids !== null, fn ($q) => $q->whereIn('board_periods.organization_id', $ids))
            ->when($request->filled('organization_id'), fn ($q) => $q->where('board_periods.organization_id', $request->input('organization_id')));

        return DataTables::eloquent($periods)
            ->editColumn('is_active', fn ($p) => $p->is_active ? 'Aktif' : '-')
            ->addColumn('action', fn ($p) => view('components.dashboard.row-actions', [
                'show' => route('board.show', $p),
                'edit' => $request->user()->can('update', $p) ? route('board.edit', $p) : null,
                'delete' => $request->user()->can('delete', $p) ? route('board.destroy', $p) : null,
                'confirm' => 'Periode beserta daftar pengurusnya akan dihapus.',
            ])->render())
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function create(Request $request)
    {
        $period = new BoardPeriod(['organization_id' => $request->query('organization_id'), 'is_active' => true]);

        return view('dashboard.board.form', ['period' => $period, 'organizations' => $this->organizationOptions($request)]);
    }

    public function store(Request $request)
    {
        $period = BoardPeriod::create($this->validated($request));

        return redirect()->route('board.show', $period)->with('success', 'Periode kepengurusan dibuat. Tambahkan pengurusnya di bawah.');
    }

    public function show(BoardPeriod $board)
    {
        $board->load('organization', 'boardMembers.user', 'boardMembers.position', 'boardMembers.division');
        $level = $board->organization->type === 'pc' ? 'pc' : 'pd';
        $positions = Position::with('division')
            ->where(fn ($q) => $q->whereNull('division_id')->orWhereHas('division', fn ($d) => $d->where('level', $level)))
            ->get()
            ->sortBy(fn ($p) => [$p->division ? 1 : 0, $p->division?->sort_order, $p->sort_order])
            ->mapWithKeys(fn ($p) => [$p->id => $p->division ? "{$p->division->name} — {$p->name}" : $p->name]);

        return view('dashboard.board.show', ['period' => $board, 'positions' => $positions]);
    }

    public function edit(Request $request, BoardPeriod $board)
    {
        return view('dashboard.board.form', ['period' => $board, 'organizations' => $this->organizationOptions($request)]);
    }

    public function update(Request $request, BoardPeriod $board)
    {
        $data = $this->validated($request, $board);
        if (isset($data['sk_file']) && $board->sk_file) {
            Storage::disk('public')->delete($board->sk_file);
        }
        $board->update($data);

        return redirect()->route('board.show', $board)->with('success', 'Periode kepengurusan diubah.');
    }

    public function destroy(BoardPeriod $board)
    {
        if ($board->sk_file) {
            Storage::disk('public')->delete($board->sk_file);
        }
        $board->delete();
        session()->flash('success', "Periode {$board->name} dihapus.");

        return response()->noContent();
    }

    /** "Buat periode baru dari periode ini" (PRD 8.4). */
    public function duplicate(BoardPeriod $board)
    {
        $this->authorize('create', BoardPeriod::class);
        $this->authorize('view', $board);
        $copy = $board->duplicate();

        return redirect()->route('board.edit', $copy)->with('success', 'Periode disalin beserta pengurusnya. Ubah nama & tanggal periode baru ini.');
    }

    /** Kandidat pengurus: anggota, atau SA/admin PD yang juga anggota (PRD 8.4). */
    public function candidates(Request $request)
    {
        $this->authorize('create', BoardPeriod::class);

        return User::role(['anggota', 'admin_pd', 'super_administrator'])
            ->where('name', 'like', '%' . $request->input('q') . '%')
            ->orderBy('name')->limit(20)->get(['id', 'name'])
            ->map(fn ($u) => ['id' => $u->id, 'text' => $u->name]);
    }

    private function validated(Request $request, ?BoardPeriod $period = null): array
    {
        $allowed = array_keys($this->organizationOptions($request)->all());
        $data = $request->validate([
            'organization_id' => ['required', Rule::in($allowed)],
            'name' => 'required|string|max:100',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'sk_number' => 'nullable|string|max:100',
            'sk_file' => 'nullable|file|mimes:pdf|max:10240',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        if ($request->hasFile('sk_file')) {
            $data['sk_file'] = $request->file('sk_file')->store('board', 'public');
        }

        return $data;
    }

    private function organizationOptions(Request $request)
    {
        $ids = $request->user()->scopedOrganizationIds();

        return Organization::inActiveRegion()->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))
            ->orderByRaw("type = 'pc'")->orderBy('name')->pluck('name', 'id');
    }
}
