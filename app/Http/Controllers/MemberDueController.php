<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberDue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Pencatatan iuran tahunan (PRD 8.3). Bukti bayar disimpan di disk privat `local`. */
class MemberDueController extends Controller
{
    public function store(Request $request, Member $membership)
    {
        $this->authorize('create', MemberDue::class);
        $this->authorize('view', $membership);

        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2000,' . (now()->year + 1), Rule::unique('member_dues')->where('member_id', $membership->id)],
            'amount' => 'required|numeric|min:0|max:9999999999',
            'paid_at' => 'required|date',
            'proof_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'notes' => 'nullable|string|max:1000',
        ], ['year.unique' => 'Iuran tahun ini sudah dicatat.']);

        if ($request->hasFile('proof_file')) {
            $data['proof_file'] = $request->file('proof_file')->store('dues', 'local');
        }

        $membership->dues()->create($data + ['recorded_by' => $request->user()->id]);

        return redirect()->route('membership.show', $membership)->with('success', "Iuran tahun {$data['year']} dicatat.");
    }

    public function destroy(MemberDue $due)
    {
        $this->authorize('delete', $due);
        if ($due->proof_file) {
            Storage::disk('local')->delete($due->proof_file);
        }
        $due->delete();

        return redirect()->route('membership.show', $due->member_id)->with('success', "Catatan iuran {$due->year} dihapus.");
    }

    public function proof(MemberDue $due)
    {
        $this->authorize('viewProof', $due);
        abort_unless($due->proof_file && Storage::disk('local')->exists($due->proof_file), 404);

        return Storage::disk('local')->download($due->proof_file, "bukti-iuran-{$due->year}." . pathinfo($due->proof_file, PATHINFO_EXTENSION));
    }
}
