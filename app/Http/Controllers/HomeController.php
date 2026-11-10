<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\DashboardData;
use Illuminate\Http\Request;

/** Beranda dashboard (PRD 9.19): kartu & grafik sesuai permission, ringkasan anggota. */
class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('dashboard.home.index', [
            'user' => $user,
            'cards' => DashboardData::cards($user),
            'charts' => DashboardData::charts($user),
            'learning' => DashboardData::member($user),
            'event' => Event::public()->where('starts_at', '>=', now())->oldest('starts_at')->first(),
            'announcements' => \App\Models\Announcement::for($user)->take(5)->get(),
            'todayDuties' => $user->member ? $user->member->duties()->with('area')->whereDate('date', today())->get() : collect(),
        ]);
    }
}
