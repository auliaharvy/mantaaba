<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

/** Pakta integritas saat siswa login pertama (PRD 9.4). */
class IntegrityPactController extends Controller
{
    public function show(Request $request)
    {
        $member = $request->user()->member;
        abort_unless($member, 404);

        return view('dashboard.pact.show', ['member' => $member, 'text' => Setting::current()->integrity_pact_text]);
    }

    public function sign(Request $request)
    {
        $member = $request->user()->member;
        abort_unless($member, 404);
        $request->validate(['agree' => 'accepted'], ['agree.accepted' => 'Centang persetujuan untuk melanjutkan.']);
        $member->update(['integrity_pact_signed_at' => $member->integrity_pact_signed_at ?? now()]);

        return redirect()->route('home')->with('success', 'Terima kasih. Pakta integritas telah Anda setujui.');
    }
}
