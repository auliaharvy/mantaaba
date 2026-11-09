<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\Setting;
use App\Services\EventRegistrar;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Pusat Pelatihan publik (PRD 7, 8.9). */
class TrainingController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'tab' => ['nullable', Rule::in(['mendatang', 'selesai'])],
            'jenis' => ['nullable', Rule::in(array_keys(Event::TYPES))],
            'mode' => ['nullable', Rule::in(array_keys(Event::MODES))],
            'bulan' => 'nullable|date_format:Y-m',
        ]);
        $tab = $data['tab'] ?? 'mendatang';

        $events = Event::public()->with('organization')
            ->when($tab === 'selesai', fn ($q) => $q->where(fn ($w) => $w->where('status', 'done')->orWhere('ends_at', '<', now()))->latest('starts_at'),
                fn ($q) => $q->where('status', '!=', 'done')->where('ends_at', '>=', now())->oldest('starts_at'))
            ->when($data['jenis'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($data['mode'] ?? null, fn ($q, $v) => $q->where('mode', $v))
            ->when($data['bulan'] ?? null, fn ($q, $v) => $q->whereYear('starts_at', substr($v, 0, 4))->whereMonth('starts_at', substr($v, 5, 2)))
            ->paginate(9)->withQueryString();

        return view('site.training.index', compact('events', 'tab'));
    }

    public function show(Request $request, string $slug)
    {
        $event = Event::public()->where('slug', $slug)->with('organization')->firstOrFail();
        $user = $request->user();

        return view('site.training.show', [
            'event' => $event,
            'registration' => $user ? $event->participants()->where('user_id', $user->id)->first() : null,
            'fee' => $user ? $event->feeFor($user) : null,
            'seatsLeft' => $event->seatsLeft(),
            'bank' => Setting::current()->bank_account_info,
        ]);
    }

    public function register(Request $request, string $slug, EventRegistrar $registrar)
    {
        $event = Event::public()->where('slug', $slug)->firstOrFail();
        $participant = $registrar->register($event, $request->user());

        return redirect()->route('my-training.show', $participant)->with('success', $participant->status === 'verified'
            ? 'Pendaftaran berhasil dan sudah terverifikasi.'
            : 'Pendaftaran diterima. Silakan transfer biaya lalu unggah bukti pembayaran.');
    }

    /** Verifikasi publik (PRD 8.9 alur 8): kode salah -> 404. */
    public function certificate(string $code)
    {
        $certificate = Certificate::where('verification_code', strtoupper($code))->whereNotNull('issued_at')
            ->with('participant.user', 'participant.event.organization')->firstOrFail();

        return view('site.training.certificate', compact('certificate'));
    }
}
