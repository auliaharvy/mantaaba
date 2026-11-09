<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Thread;
use App\Models\Research;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        $counter = [
            'blog' => Blog::published()->count(),
            'thread' => Thread::published()->count(),
            'research' =>  Research::select('id')->count(),
            'event' => Event::select('id')->count(),
            'user' => User::select('id')->count()
        ];

        $members = User::select('id')->role(['super_administrator','admin_pd','anggota'])->get();
        $generals = User::select('id')->role('umum')->get();
        $userRoles = [
            'member' => $members->count(),
            'general' => $generals->count()
        ];

        $event = Event::public()->latest('starts_at')->first();
        // B8: dashboard tetap jalan sebelum ada event
        $date_event = $event
            ? $event->starts_at?->translatedFormat('j M Y') . ' - ' . $event->ends_at?->translatedFormat('j M Y')
            : null;

        return view('dashboard.home.index', compact(
            'counter',
            'userRoles',
            'event',
            'date_event'
        ));
    }
}
