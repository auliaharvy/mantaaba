<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/** Usulan skors (PRD 8.3): anggota aktif yang menunggak; status diubah hanya setelah konfirmasi admin. */
class SuspensionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:member-update');
    }

    public static function candidates(Request $request)
    {
        return Member::select('members.*')->with('user', 'organization', 'dues')
            ->visibleTo($request->user())->where('members.status', 'aktif')->duesStatus('menunggak');
    }

    public function index()
    {
        return view('dashboard.membership.suspensions');
    }

    public function datatable(Request $request)
    {
        return DataTables::eloquent(self::candidates($request))
            ->addColumn('last_paid', fn ($m) => $m->dues->first()?->year ?? 'Belum pernah')
            ->addColumn('action', fn ($m) => view('dashboard.membership.suspend-button', ['member' => $m])->render())
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function update(Member $membership)
    {
        $this->authorize('update', $membership);
        abort_unless($membership->status === 'aktif', 422, 'Hanya anggota aktif yang bisa diskors.');
        $membership->update(['status' => 'skors']); // tercatat di activity_logs (status_changed)

        return back()->with('success', "{$membership->user->name} diubah menjadi status skors.");
    }
}
