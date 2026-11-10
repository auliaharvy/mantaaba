<?php

namespace App\Http\Controllers;

use App\Models\Duty;
use Illuminate\Http\Request;

/** Piket saya (PRD 9.9): ceklis tugas area → Selesai (opsional foto). */
class MyDutyController extends Controller
{
    public function index(Request $request)
    {
        $member = $request->user()->member;
        abort_unless($member, 404);
        $today = $member->duties()->with('area')->whereDate('date', today())->get();
        $upcoming = $member->duties()->with('area')->where('date', '>', today())->orderBy('date')->take(14)->get();
        $history = $member->duties()->with('area')->where('date', '<', today())->latest('date')->take(10)->get();

        return view('dashboard.duty.mine', compact('today', 'upcoming', 'history') + ['rate' => Duty::completionRate($member->id, today()->startOfMonth(), today())]);
    }

    public function complete(Request $request, Duty $duty)
    {
        abort_unless($duty->member->user_id === $request->user()->id, 403);
        abort_unless($duty->status === 'terjadwal' && $duty->date->isToday(), 422, 'Hanya piket hari ini yang belum selesai yang bisa ditandai.');
        $data = $request->validate(['checklist' => 'nullable|array', 'checklist.*' => 'string|max:255', 'note' => 'nullable|string|max:500', 'photo' => 'nullable|image|max:5120']);
        $required = $duty->area->checklist ?? [];
        if (array_diff($required, $data['checklist'] ?? [])) {
            return back()->withErrors(['checklist' => 'Centang semua tugas di ceklis area sebelum menandai selesai.']);
        }
        $duty->update([
            'status' => 'selesai', 'done_at' => now(), 'checklist_done' => $data['checklist'] ?? [], 'note' => $data['note'] ?? null,
            'photo' => $request->hasFile('photo') ? $request->file('photo')->store('duties', 'local') : null,
        ]);

        return back()->with('success', 'Piket selesai. Terima kasih!');
    }
}
