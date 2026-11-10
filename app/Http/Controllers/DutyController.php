<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Duty;
use App\Models\DutyArea;
use App\Models\Member;
use App\Services\DutyGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Jadwal piket (PRD 9.9): jadwal per tanggal, generator rotasi, ubah/tukar, verifikasi, laporan. */
class DutyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dorm-view')->only(['index', 'report']);
        $this->middleware('permission:dorm-create')->only(['generate']);
        $this->middleware('permission:dorm-update')->only(['update', 'swap']);
        $this->middleware('permission:dorm-delete')->only(['destroy']);
        $this->middleware('permission:duty-verify')->only(['verify']);
    }

    public function index(Request $request)
    {
        $from = Carbon::parse($request->query('dari', today()->toDateString()));
        $to = Carbon::parse($request->query('sampai', $from->copy()->addDays(6)->toDateString()));
        $duties = Duty::with('area', 'member:id,name')->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->when($request->filled('area'), fn ($q) => $q->where('duty_area_id', $request->input('area')))
            ->orderBy('date')->orderByRaw("FIELD(shift, 'pagi', 'siang', 'sore', 'malam')")->get()->groupBy(fn ($d) => $d->date->toDateString());

        return view('dashboard.duty.index', [
            'duties' => $duties, 'from' => $from, 'to' => $to,
            'areas' => DutyArea::orderBy('name')->get(),
            'members' => $this->activeMembers(),
            'batches' => Batch::whereIn('status', ['running', 'done'])->orderByDesc('code')->get(['id', 'code']),
        ]);
    }

    public function generate(Request $request, DutyGenerator $generator)
    {
        $data = $request->validate([
            'duty_area_id' => 'required|exists:duty_areas,id',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from|before_or_equal:' . Carbon::parse($request->input('from'))->addDays(62)->toDateString(),
            'shifts' => 'required|array|min:1',
            'shifts.*' => [Rule::in(array_keys(Duty::SHIFTS))],
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'integer|exists:members,id',
            'per_shift' => 'required|integer|min:1|max:10',
        ], ['to.before_or_equal' => 'Rentang maksimal 2 bulan sekali buat.', 'member_ids.required' => 'Pilih anggota yang dirotasi.']);
        $result = $generator->generate(DutyArea::find($data['duty_area_id']), Carbon::parse($data['from']), Carbon::parse($data['to']), $data['shifts'], $data['member_ids'], (int) $data['per_shift']);

        $redirect = redirect()->route('duty.index', ['dari' => $data['from'], 'sampai' => $data['to'], 'area' => $data['duty_area_id']])
            ->with('success', "{$result['created']} jadwal piket dibuat.");

        return $result['short'] ? $redirect->with('warning', 'Slot belum terisi (anggota sudah piket di hari yang sama): ' . implode(', ', $result['short'])) : $redirect;
    }

    /** Ganti petugas satu jadwal (tetap tidak boleh dua kali sehari). */
    public function update(Request $request, Duty $duty)
    {
        $memberId = (int) $request->validate(['member_id' => 'required|exists:members,id'])['member_id'];
        $this->ensureFree($memberId, $duty->date->toDateString(), $duty->id);
        $duty->update(['member_id' => $memberId, 'status' => 'terjadwal', 'done_at' => null]);

        return back()->with('success', 'Petugas piket diganti.');
    }

    /** Tukar jadwal dua anggota. */
    public function swap(Request $request)
    {
        $data = $request->validate(['first' => 'required|exists:duties,id', 'second' => 'required|exists:duties,id|different:first']);
        DB::transaction(function () use ($data) {
            [$a, $b] = [Duty::lockForUpdate()->find($data['first']), Duty::lockForUpdate()->find($data['second'])];
            if ($a->status !== 'terjadwal' || $b->status !== 'terjadwal') {
                throw ValidationException::withMessages(['first' => 'Hanya jadwal yang belum dikerjakan yang bisa ditukar.']);
            }
            if (! $a->date->equalTo($b->date)) {
                $this->ensureFree($b->member_id, $a->date->toDateString(), $a->id);
                $this->ensureFree($a->member_id, $b->date->toDateString(), $b->id);
            }
            [$aMember, $bMember] = [$a->member_id, $b->member_id];
            $a->update(['member_id' => $bMember]);
            $b->update(['member_id' => $aMember]);
        });

        return back()->with('success', 'Jadwal ditukar.');
    }

    public function destroy(Duty $duty)
    {
        $duty->delete();
        session()->flash('success', 'Jadwal dihapus.');

        return response()->noContent();
    }

    public function verify(Duty $duty)
    {
        abort_unless($duty->status === 'selesai', 422, 'Hanya piket yang sudah selesai yang bisa diverifikasi.');
        $duty->update(['status' => 'diverifikasi', 'verified_by' => auth()->id(), 'verified_at' => now()]);

        return back()->with('success', 'Piket diverifikasi.');
    }

    public function photo(Request $request, Duty $duty)
    {
        abort_unless($request->user()->can('dorm-view') || $duty->member->user_id === $request->user()->id, 403);
        abort_unless($duty->photo && Storage::disk('local')->exists($duty->photo), 404);

        return Storage::disk('local')->response($duty->photo);
    }

    /** Laporan persentase piket selesai per anggota per bulan (PRD 9.9). */
    public function report(Request $request)
    {
        $month = Carbon::createFromFormat('Y-m', $request->query('bulan', now()->format('Y-m')))->startOfMonth();
        $rows = Duty::with('member:id,name,member_number,batch_id')->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->get()->groupBy('member_id')->map(function ($duties) {
                $due = $duties->filter(fn ($d) => $d->status !== 'terjadwal' || $d->date->lt(today()));
                $done = $due->filter(fn ($d) => in_array($d->status, Duty::DONE, true))->count();

                return ['member' => $duties->first()->member, 'total' => $duties->count(), 'due' => $due->count(), 'done' => $done,
                    'missed' => $duties->where('status', 'terlewat')->count(), 'percent' => $due->count() ? (int) round($done / $due->count() * 100) : null];
            })->sortBy(fn ($r) => $r['member']->name)->values();

        return view('dashboard.duty.report', compact('rows', 'month'));
    }

    private function ensureFree(int $memberId, string $date, int $ignoreDutyId): void
    {
        if (Duty::where('member_id', $memberId)->whereDate('date', $date)->whereKeyNot($ignoreDutyId)->exists()) {
            throw ValidationException::withMessages(['member_id' => 'Anggota ini sudah punya piket di hari yang sama.']);
        }
    }

    private function activeMembers()
    {
        return Member::where('status', 'aktif')->whereIn('category', ['siswa', 'alumni', 'pengurus', 'relawan'])->orderBy('name')->get(['id', 'name', 'batch_id', 'category']);
    }
}
