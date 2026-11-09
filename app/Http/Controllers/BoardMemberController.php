<?php

namespace App\Http\Controllers;

use App\Models\BoardMember;
use App\Models\BoardPeriod;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BoardMemberController extends Controller
{
    public function store(Request $request, BoardPeriod $board)
    {
        $this->authorize('update', $board);
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'position_id' => ['required', 'exists:positions,id',
                Rule::unique('board_members')->where('board_period_id', $board->id)->where('user_id', $request->input('user_id'))],
            'sort_order' => 'nullable|integer|min:0',
        ], ['position_id.unique' => 'Pengurus ini sudah memegang jabatan tersebut di periode ini.']);

        // PRD 8.4: pengurus wajib anggota, kecuali SA/admin PD yang juga anggota
        if (! User::find($data['user_id'])->hasAnyRole(['anggota', 'admin_pd', 'super_administrator'])) {
            return back()->withErrors(['user_id' => 'Pengurus harus berstatus anggota.'])->withInput();
        }

        $board->boardMembers()->create([
            'user_id' => $data['user_id'],
            'position_id' => $data['position_id'],
            'division_id' => Position::find($data['position_id'])->division_id,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()->route('board.show', $board)->with('success', 'Pengurus ditambahkan.');
    }

    public function destroy(BoardMember $boardMember)
    {
        $this->authorize('update', $boardMember->period);
        $boardMember->delete();

        return redirect()->route('board.show', $boardMember->board_period_id)->with('success', 'Pengurus dihapus dari periode ini.');
    }
}
