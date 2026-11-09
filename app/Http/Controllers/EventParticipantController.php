<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use App\Notifications\EventAnnouncement;
use App\Notifications\RegistrationStatusChanged;
use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/** Peserta satu event (PRD 8.9 alur 4 & 6 + fitur admin). Akses = pengelola event (scope PC). */
class EventParticipantController extends Controller
{
    public function datatable(Request $request, Event $event)
    {
        $this->authorize('view', $event);
        $participants = Participant::select('participants.*')->with('user', 'certificateRecord')->where('event_id', $event->id)
            ->when($request->filled('status'), fn ($q) => $q->where('participants.status', $request->input('status')));

        return DataTables::eloquent($participants)
            ->addColumn('check', fn ($p) => '<input type="checkbox" class="form-check-input" name="ids[]" value="' . $p->id . '" form="attendance-form" aria-label="Pilih ' . e($p->user->name) . '">')
            ->editColumn('status', fn ($p) => Participant::STATUSES[$p->status])
            ->editColumn('amount', fn ($p) => 'Rp' . number_format($p->amount, 0, ',', '.'))
            ->editColumn('attended_at', fn ($p) => $p->attended_at?->translatedFormat('d M H:i') ?? '-')
            ->addColumn('certificate_state', fn ($p) => $p->certificateRecord?->isValid() ? 'Terbit' : ($p->certificateRecord?->revoked_at ? 'Dicabut' : ($p->certificate ? 'Manual' : '-')))
            ->addColumn('action', fn ($p) => view('dashboard.event.participant-action', ['participant' => $p, 'event' => $event])->render())
            ->rawColumns(['check', 'action'])
            ->addIndexColumn()
            ->make(true);
    }

    /** Tambah peserta manual (pendaftaran offline): langsung terverifikasi. */
    public function store(Request $request, Event $event)
    {
        $this->authorize('update', $event);
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id', Rule::unique('participants')->where('event_id', $event->id)],
        ], ['user_id.unique' => 'Pengguna ini sudah terdaftar di event ini.']);
        $user = User::findOrFail($data['user_id']);
        $participant = $event->participants()->create([
            'user_id' => $user->id, 'status' => 'verified', 'amount' => $event->feeFor($user),
            'verified_by' => $request->user()->id, 'verified_at' => now(), 'notes' => 'Didaftarkan admin',
        ]);
        $user->notify(new RegistrationStatusChanged($participant));

        return back()->with('success', "{$user->name} ditambahkan sebagai peserta.");
    }

    /** Verifikasi / tolak (dengan alasan) / batalkan. */
    public function status(Request $request, Participant $participant)
    {
        $this->authorize('update', $participant);
        $data = $request->validate([
            'status' => 'required|in:verified,rejected,cancelled,pending',
            'notes' => 'required_if:status,rejected|nullable|string|max:500',
        ], ['notes.required_if' => 'Alasan penolakan wajib diisi.']);

        $participant->update([
            'status' => $data['status'],
            'notes' => $data['notes'] ?? $participant->notes,
            'verified_by' => $data['status'] === 'verified' ? $request->user()->id : $participant->verified_by,
            'verified_at' => $data['status'] === 'verified' ? now() : $participant->verified_at,
        ]);
        $participant->user->notify(new RegistrationStatusChanged($participant));

        return back()->with('success', "Status {$participant->user->name}: " . Participant::STATUSES[$data['status']] . '.');
    }

    /** Tandai hadir / batal hadir massal dari tabel (PRD 8.9 alur 6). */
    public function attendance(Request $request, Event $event)
    {
        $this->authorize('update', $event);
        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer', 'present' => 'required|boolean']);
        $count = $event->participants()->whereIn('id', $data['ids'])->where('status', 'verified')
            ->update(['attended_at' => $data['present'] ? now() : null]);

        return back()->with('success', "{$count} peserta " . ($data['present'] ? 'ditandai hadir.' : 'dibatalkan kehadirannya.'));
    }

    public function checkInPage(Event $event)
    {
        $this->authorize('update', $event);

        return view('dashboard.event.check-in', compact('event'));
    }

    /** Check-in dengan kode tiket (hasil pindai QR atau ketik manual). */
    public function checkIn(Request $request, Event $event)
    {
        $this->authorize('update', $event);
        $code = strtoupper(trim((string) $request->validate(['code' => 'required|string|max:20'])['code']));
        $participant = $event->participants()->with('user')->where('ticket_code', $code)->first();

        if (! $participant) {
            return response()->json(['ok' => false, 'message' => "Tiket {$code} tidak terdaftar di event ini."], 404);
        }
        if ($participant->status !== 'verified') {
            return response()->json(['ok' => false, 'message' => "{$participant->user->name}: pendaftaran belum terverifikasi."], 422);
        }
        $already = $participant->attended_at !== null;
        if (! $already) {
            $participant->update(['attended_at' => now()]);
        }

        return response()->json(['ok' => true, 'message' => $participant->user->name . ($already ? ' sudah check-in sebelumnya.' : ' berhasil check-in.')]);
    }

    public function proof(Participant $participant)
    {
        $this->authorize('viewProof', $participant);
        abort_unless($participant->payment_proof && Storage::disk('local')->exists($participant->payment_proof), 404);

        return Storage::disk('local')->response($participant->payment_proof);
    }

    /** Upload sertifikat manual (cadangan, fitur lama baraasa). */
    public function manualCertificate(Request $request, Participant $participant)
    {
        $this->authorize('update', $participant);
        $request->validate(['certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120']);
        $participant->update(['certificate' => Upload::store($request->file('certificate'), 'participant/certificate', $participant->certificate)]);

        return back()->with('success', 'Sertifikat manual diunggah.');
    }

    public function export(Event $event)
    {
        $this->authorize('view', $event);

        return response()->streamDownload(function () use ($event) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['nama', 'email', 'telepon', 'status', 'biaya', 'terverifikasi', 'hadir', 'kode_tiket', 'nomor_sertifikat']);
            $event->participants()->with('user', 'certificateRecord')->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $p) {
                    fputcsv($out, [$p->user->name, $p->user->email, $p->user->phone_number, $p->status, $p->amount,
                        $p->verified_at?->format('Y-m-d H:i'), $p->attended_at?->format('Y-m-d H:i'), $p->ticket_code, $p->certificateRecord?->number]);
                }
            });
            fclose($out);
        }, "peserta-{$event->code}.csv", ['Content-Type' => 'text/csv']);
    }

    /** Email pengumuman ke semua peserta terverifikasi (PRD 8.9 fitur admin). */
    public function announce(Request $request, Event $event)
    {
        $this->authorize('update', $event);
        $data = $request->validate(['subject' => 'required|string|max:150', 'message' => 'required|string|max:5000']);
        $users = User::whereIn('id', $event->participants()->where('status', 'verified')->select('user_id'))->get();
        Notification::send($users, new EventAnnouncement($event, $data['subject'], $data['message']));

        return back()->with('success', "Pengumuman dikirim ke {$users->count()} peserta.");
    }

    public function candidates(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        return User::where('is_active', true)
            ->where(fn ($q) => $q->where('name', 'like', '%' . $request->input('q') . '%')->orWhere('email', 'like', '%' . $request->input('q') . '%'))
            ->whereNotIn('id', $event->participants()->select('user_id'))
            ->orderBy('name')->limit(20)->get(['id', 'name', 'email'])
            ->map(fn ($u) => ['id' => $u->id, 'text' => "{$u->name} ({$u->email})"]);
    }
}
