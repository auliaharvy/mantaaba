<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Event;
use App\Notifications\EventTicket;
use App\Services\EventRegistrar;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/** Agenda publik (PRD 9.8): daftar, kalender bulanan, pendaftaran tanpa akun, tiket QR. */
class TrainingController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'tab' => ['nullable', Rule::in(['mendatang', 'selesai', 'kalender'])],
            'jenis' => ['nullable', Rule::in(array_keys(Event::TYPES))],
            'bulan' => 'nullable|date_format:Y-m',
        ]);
        $tab = $data['tab'] ?? 'mendatang';
        $month = Carbon::createFromFormat('Y-m-d', ($data['bulan'] ?? now()->format('Y-m')) . '-01')->startOfMonth();

        if ($tab === 'kalender') {
            $byDay = Event::public()->whereBetween('starts_at', [$month, $month->copy()->endOfMonth()])->orderBy('starts_at')->get()
                ->groupBy(fn ($e) => $e->starts_at->toDateString());

            return view('site.training.index', compact('tab', 'month', 'byDay') + ['events' => null]);
        }

        $events = Event::public()
            ->when($tab === 'selesai', fn ($q) => $q->where(fn ($w) => $w->where('status', 'done')->orWhere('ends_at', '<', now()))->latest('starts_at'),
                fn ($q) => $q->where('status', '!=', 'done')->where('ends_at', '>=', now())->oldest('starts_at'))
            ->when($data['jenis'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->paginate(9)->withQueryString();

        return view('site.training.index', compact('events', 'tab', 'month'));
    }

    public function show(Request $request, string $slug)
    {
        $event = Event::public()->where('slug', $slug)->firstOrFail();
        $user = $request->user();

        return view('site.training.show', [
            'event' => $event,
            'registration' => $user ? $event->participants()->where('user_id', $user->id)->where('status', '!=', 'batal')->first() : null,
            'seatsLeft' => $event->seatsLeft(),
        ]);
    }

    public function register(Request $request, string $slug, EventRegistrar $registrar)
    {
        $event = Event::public()->where('slug', $slug)->firstOrFail();
        if (filled($request->input('website'))) { // honeypot
            return redirect()->route('site.training.show', $slug);
        }
        $user = $request->user();
        $guest = $user ? [] : $request->validate([
            'guest_name' => 'required|string|max:100',
            'guest_phone' => 'required|string|max:30|regex:/^[0-9+\-\s]{9,}$/',
            'guest_email' => 'nullable|email|max:255',
        ], [], ['guest_name' => 'nama', 'guest_phone' => 'nomor WhatsApp', 'guest_email' => 'email']);
        $participant = $registrar->register($event, $user, $guest);

        $ticketUrl = URL::signedRoute('site.training.ticket', [$event->slug, $participant->ticket_code]);
        if ($email = $participant->email()) {
            Notification::route('mail', $email)->notify(new EventTicket($participant, $ticketUrl));
        }

        return redirect($ticketUrl)->with('success', 'Pendaftaran berhasil. Simpan tiket ini.');
    }

    /** Tiket QR (tautan bertanda tangan, juga dikirim lewat email). */
    public function ticket(string $slug, string $code)
    {
        $event = Event::where('slug', $slug)->firstOrFail();
        $participant = $event->participants()->where('ticket_code', $code)->firstOrFail();

        return view('site.training.ticket', [
            'event' => $event, 'participant' => $participant,
            'qr' => QrCode::format('svg')->size(220)->margin(1)->generate($participant->ticket_code),
        ]);
    }

    /** Verifikasi publik (PRD 9.7): nama, skema, tanggal, status valid/dicabut; kode salah -> 404. */
    public function certificate(string $code)
    {
        $certificate = Certificate::where('verification_code', strtoupper($code))->whereNotNull('issued_at')
            ->with('member.batch', 'result.enrollment.skema')->firstOrFail();

        return view('site.training.certificate', compact('certificate'));
    }
}
