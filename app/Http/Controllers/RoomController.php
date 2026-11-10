<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Kamar asrama & penghuni (PRD 9.9). Hard-stop: kamar penuh atau gender tidak cocok. */
class RoomController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dorm-view')->only('index');
        $this->authorizeResource(Room::class, 'room', ['except' => ['index', 'show']]);
    }

    public function index()
    {
        $rooms = Room::with('residents:id,name,member_number,room_id,gender')->orderBy('building')->orderBy('code')->get();
        $unassigned = Member::where('status', 'aktif')->whereIn('category', ['siswa', 'alumni', 'pengurus', 'relawan'])->whereNull('room_id')
            ->orderBy('name')->get(['id', 'name', 'member_number', 'gender']);

        return view('dashboard.room.index', compact('rooms', 'unassigned'));
    }

    public function create()
    {
        return view('dashboard.room.form', ['room' => new Room(['capacity' => 4, 'gender' => 'L'])]);
    }

    public function store(Request $request)
    {
        Room::create($this->validated($request));

        return redirect()->route('room.index')->with('success', 'Kamar ditambahkan.');
    }

    public function edit(Room $room)
    {
        return view('dashboard.room.form', compact('room'));
    }

    public function update(Request $request, Room $room)
    {
        $data = $this->validated($request, $room);
        if ($data['capacity'] < $room->residents()->count()) {
            throw ValidationException::withMessages(['capacity' => 'Kapasitas tidak boleh lebih kecil dari jumlah penghuni sekarang.']);
        }
        if ($data['gender'] !== $room->gender && $room->residents()->exists()) {
            throw ValidationException::withMessages(['gender' => 'Kosongkan kamar dulu sebelum mengubah peruntukan putra/putri.']);
        }
        $room->update($data);

        return redirect()->route('room.index')->with('success', 'Kamar diubah.');
    }

    public function destroy(Room $room)
    {
        abort_if($room->residents()->exists(), 422, 'Kamar masih berpenghuni.');
        $room->delete();
        session()->flash('success', 'Kamar dihapus.');

        return response()->noContent();
    }

    /** Tempatkan penghuni (pindah kamar juga lewat sini). */
    public function assign(Request $request, Room $room)
    {
        $this->authorize('update', $room);
        $member = Member::findOrFail($request->validate(['member_id' => 'required|exists:members,id'])['member_id']);
        DB::transaction(function () use ($room, $member) {
            $room = Room::whereKey($room->id)->lockForUpdate()->first();
            if (! $member->gender) {
                throw ValidationException::withMessages(['member_id' => "Lengkapi jenis kelamin {$member->name} di data anggota dulu."]);
            }
            if ($member->gender !== $room->gender) {
                throw ValidationException::withMessages(['member_id' => "Kamar {$room->code} untuk " . Str::lower(Room::GENDERS[$room->gender]) . '.']);
            }
            if ($member->room_id !== $room->id && $room->residents()->count() >= $room->capacity) {
                throw ValidationException::withMessages(['member_id' => "Kamar {$room->code} penuh ({$room->capacity} orang)."]);
            }
            $member->update(['room_id' => $room->id]);
        });

        return back()->with('success', "{$member->name} ditempatkan di kamar {$room->code}.");
    }

    public function unassign(Member $member)
    {
        $this->authorize('update', Room::class);
        $member->update(['room_id' => null]);

        return back()->with('success', "{$member->name} dikeluarkan dari kamar.");
    }

    private function validated(Request $request, ?Room $room = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('rooms')->ignore($room)],
            'name' => 'required|string|max:100',
            'building' => 'nullable|string|max:100',
            'capacity' => 'required|integer|min:1|max:50',
            'gender' => ['required', Rule::in(array_keys(Room::GENDERS))],
            'notes' => 'nullable|string|max:1000',
        ]);
    }
}
