<?php

namespace App\Http\Controllers;

use App\Services\DashboardData;
use App\Support\Menu;
use Illuminate\Http\Request;

/** Beranda = peluncur modul (ikon); ringkasan angka & grafik ada di halaman ringkasan tiap modul (PRD 9.19). */
class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('dashboard.home.index', [
            'user' => $user,
            'modules' => Menu::modules($user),
            'announcements' => \App\Models\Announcement::for($user)->take(3)->get(),
        ]);
    }

    /** Ringkasan satu modul: pintasan submenu, kartu, grafik, dan (modul Saya) ringkasan pribadi. */
    public function overview(Request $request, string $key)
    {
        $user = $request->user();
        $module = Menu::modules($user)->get($key);
        abort_unless($module, isset(config('mantaaba.menu.modules')[$key]) ? 403 : 404);

        return view('dashboard.home.overview', [
            'module' => $module,
            'cards' => DashboardData::cards($user, $key),
            'charts' => DashboardData::charts($user, $key),
            'learning' => $key === 'saya' ? DashboardData::member($user) : null,
            'todayDuties' => $key === 'saya' && $user->member ? $user->member->duties()->with('area')->whereDate('date', today())->get() : collect(),
            'event' => $key === 'saya' ? \App\Models\Event::public()->where('starts_at', '>=', now())->oldest('starts_at')->first() : null,
        ]);
    }
}
