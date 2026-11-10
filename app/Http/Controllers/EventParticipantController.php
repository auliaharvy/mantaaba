<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Member;
use App\Models\Participant;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\EventAnnouncement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/** Peserta satu agenda (PRD 9.8): kehadiran massal & check-in QR (tiket atau kartu anggota). */
class EventParticipantController extends Controller
{
    public function datatable(Request $request, Event $event)
    {
        $this->authorize('view', $event);
        $participants = Participant::select('participants.*')->with('user')->where('event_id', $event->id)
            ->when($request->filled('status'), fn ($q) => $q->where('participants.status', $request->input('status')));

        return DataTables::eloquent($participants)
            ->addColumn('check', fn ($p) => '<input type="checkbox" class="form-check-input" name="ids[]" value="' . $p->id . '" form="attendance-form" aria-label="Pilih ' . e($p->name()) . '">')
            ->addColumn('name', fn ($p) => e($p->name()) . ($p->user_id ? '' : ' <span class="badge badge-light fs-8">tamu</span>'))
            ->addColumn('contact', fn ($p) => e(collect([$p->phone(), $p->email()])->filter()->implode(' · ')))
            ->editColumn('status', fn ($p) => Participant::STATUSES[$p->status])
            ->editColumn('attended_at', fn ($p) => $p->attended_at?->translatedFormat('d M H:i') ?? '-')
            ->addColumn('action', fn ($p) => view('dashboard.event.participant-action', ['participant' => $p])->render())
            ->filterColumn('name', fn ($q, $k) => $q->where(fn ($w) => $w->where('guest_name', 'like', "%{$k}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$k}%"))))
            ->rawColumns(['check', 'name', 'action'])->addIndexColumn()->make(true);
    }

    /** Tambah peserta pengguna secara manual. */
    public function store(Request $request, Event $event)
    {
        $this->authorize('update', $event);
        $data = $request->validate(['user_id' => ['required', 'exists:users,id', Rule::unique('participants')->where('event_id', $event->id)]],
            ['user_id.unique' => 'Pengguna ini sudah menjadi peserta agenda ini.']);
        $participant = $event->participants()->create(['user_id' => $data['user_id'], 'notes' => 'Ditambahkan pengurus']);

        return back()->with('success', "{$participant->name()} ditambahkan sebagai peserta.");
    }

    public function sync(Event $event)
    {
        $this->authorize('update', $event);

        return back()->with('success', $event->syncParticipants() . ' peserta baru ditambahkan dari sasaran agenda.');
    }

    public function status(Request $request, Participant $participant)
    {
        $this->authorize('update', $participant);
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Participant::STATUSES))]]);
        $participant->update(['status' => $data['status'], 'attended_at' => $data['status'] === 'hadir' ? ($participant->attended_at ?? now()) : null]);

        return back()->with('success', "Status {$participant->name()}: " . Participant::STATUSES[$data['status']] . '.');
    }

    /** Tandai hadir / batal hadir massal. */
    public function attendance(Request $request, Event $event)
    {
        $this->authorize('update', $event);
        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer', 'present' => 'required|boolean']);
        $count = $event->participants()->whereIn('id', $data['ids'])->where('status', '!=', 'batal')
            ->update($data['present'] ? ['status' => 'hadir', 'attended_at' => now()] : ['status' => 'terdaftar', 'attended_at' => null]);

        return back()->with('success', "{$count} peserta " . ($data['present'] ? 'ditandai hadir.' : 'dibatalkan kehadirannya.'));
    }

    public function checkInPage(Event $event)
    {
        $this->authorize('update', $event);

        return view('dashboard.event.check-in', compact('event'));
    }

    /** Check-in dengan kode tiket atau QR kartu anggota (berisi nomor MTB-...). */
    public function checkIn(Request $request, Event $event)
    {
        $this->authorize('update', $event);
        $raw = (string) $request->validate(['code' => 'required|string|max:255'])['code'];
        $code = strtoupper(trim(basename(parse_url($raw, PHP_URL_PATH) ?: $raw)));

        $participant = $event->participants()->with('user')->where('ticket_code', $code)->first();
        if (! $participant && str_starts_with($code, 'MTB-') && ($member = Member::where('member_number', $code)->first())) {
            $participant = $member->user_id ? $event->participants()->with('user')->where('user_id', $member->user_id)->first() : null;
            // agenda internal: anggota sasaran yang belum tercatat (mis. baru bergabung) langsung ditambahkan
            if (! $participant && $member->user_id && $event->audience !== 'publik' && $event->targetUsers()->whereKey($member->user_id)->exists()) {
                $participant = $event->participants()->create(['user_id' => $member->user_id]);
            }
        }
        if (! $participant) {
            return response()->json(['ok' => false, 'message' => "Kode {$code} tidak terdaftar di agenda ini."], 404);
        }
        if ($participant->status === 'batal') {
            return response()->json(['ok' => false, 'message' => "{$participant->name()}: pendaftaran dibatalkan."], 422);
        }
        $already = $participant->status === 'hadir';
        $participant->markPresent();

        return response()->json(['ok' => true, 'message' => $participant->name() . ($already ? ' sudah check-in sebelumnya.' : ' berhasil check-in.')]);
    }

    public function export(Event $event)
    {
        $this->authorize('view', $event);

        return response()->streamDownload(function () use ($event) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['nama', 'jenis', 'telepon', 'email', 'status', 'hadir', 'kode_tiket']);
            $event->participants()->with('user.member')->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $p) {
                    fputcsv($out, [$p->name(), $p->user_id ? 'anggota/pengguna' : 'tamu', $p->phone(), $p->email(), $p->status, $p->attended_at?->format('Y-m-d H:i'), $p->ticket_code]);
                }
            });
            fclose($out);
        }, 'peserta-' . $event->slug . '.csv', ['Content-Type' => 'text/csv']);
    }

    /** Email pengumuman ke semua peserta yang punya email. */
    public function announce(Request $request, Event $event)
    {
        $this->authorize('update', $event);
        $data = $request->validate(['subject' => 'required|string|max:150', 'message' => 'required|string|max:5000']);
        $participants = $event->participants()->with('user')->where('status', '!=', 'batal')->get();
        $users = $participants->pluck('user')->filter();
        Notification::send($users, new EventAnnouncement($event, $data['subject'], $data['message']));
        $guests = $participants->whereNull('user_id')->pluck('guest_email')->filter()->unique();
        $guests->each(fn ($email) => Notification::route('mail', $email)->notify(new EventAnnouncement($event, $data['subject'], $data['message'])));

        return back()->with('success', 'Pengumuman dikirim ke ' . ($users->count() + $guests->count()) . ' email peserta.');
    }

    public function candidates(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        return User::where('is_active', true)
            ->where(fn ($q) => $q->where('name', 'like', '%' . $request->input('q') . '%')->orWhere('email', 'like', '%' . $request->input('q') . '%'))
            ->whereNotIn('id', $event->participants()->whereNotNull('user_id')->select('user_id'))
            ->orderBy('name')->limit(20)->get(['id', 'name', 'email'])
            ->map(fn ($u) => ['id' => $u->id, 'text' => "{$u->name} ({$u->email})"]);
    }

    /** Tautan WA ke peserta (dipakai tombol di tabel). */
    public static function whatsapp(Participant $participant, string $text): ?string
    {
        return Setting::waLink($participant->phone(), $text);
    }
}
