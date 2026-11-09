<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/** Pelatihan milik user (PRD 8.9 alur 3 & 5, 8.11): tiket QR, meeting_url, bukti bayar, sertifikat. */
class MyTrainingController extends Controller
{
    public function index(Request $request)
    {
        $participations = $request->user()->participants()->with('event', 'certificateRecord')->latest()->get();

        return view('dashboard.my-training.index', compact('participations'));
    }

    public function show(Participant $participant)
    {
        $this->authorize('view', $participant);
        $participant->load('event.organization', 'certificateRecord');

        return view('dashboard.my-training.show', [
            'participant' => $participant,
            'event' => $participant->event,
            'bank' => Setting::current()->bank_account_info,
            // QR hanya untuk peserta terverifikasi (tiket check-in)
            'qr' => $participant->status === 'verified' ? QrCode::format('svg')->size(220)->margin(1)->generate($participant->ticket_code) : null,
        ]);
    }

    public function uploadProof(Request $request, Participant $participant)
    {
        $this->authorize('uploadProof', $participant);
        $request->validate(['payment_proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120']);

        if ($participant->payment_proof) {
            Storage::disk('local')->delete($participant->payment_proof);
        }
        $participant->update(['payment_proof' => $request->file('payment_proof')->store('payment-proofs', 'local')]);

        return back()->with('success', 'Bukti pembayaran diunggah. Panitia akan memverifikasi pendaftaran Anda.');
    }
}
