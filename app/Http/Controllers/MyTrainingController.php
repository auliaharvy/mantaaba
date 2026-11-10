<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/** Agenda milik pengguna: daftar & tiket QR (PRD 9.8). */
class MyTrainingController extends Controller
{
    public function index(Request $request)
    {
        $participations = $request->user()->participants()->with('event')->latest()->get();

        return view('dashboard.my-training.index', compact('participations'));
    }

    public function show(Participant $participant)
    {
        $this->authorize('view', $participant);
        $participant->load('event');

        return view('dashboard.my-training.show', [
            'participant' => $participant, 'event' => $participant->event,
            'qr' => $participant->status !== 'batal' ? QrCode::format('svg')->size(220)->margin(1)->generate($participant->ticket_code) : null,
        ]);
    }
}
