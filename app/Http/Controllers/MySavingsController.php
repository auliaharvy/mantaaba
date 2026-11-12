<?php

namespace App\Http\Controllers;

use App\Services\MemberSavings;
use Illuminate\Http\Request;

/** "Titipan Saya": anggota melihat saldo & mutasi titipan/tabungannya sendiri (hanya miliknya). */
class MySavingsController extends Controller
{
    public function index(Request $request)
    {
        $member = $request->user()->member;
        abort_unless($member, 404);

        return view('dashboard.my-savings.index', ['member' => $member] + MemberSavings::statement($member));
    }
}
