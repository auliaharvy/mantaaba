<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;

/** Beranda dashboard. Kartu tampil sesuai permission; dashboard lengkap per peran di M8 (PRD 9.19). */
class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $cards = array_filter([
            $user->can('news-view') ? ['Berita terbit', Blog::published()->count(), 'fa-newspaper', route('blog.index')] : null,
            $user->can('agenda-view') ? ['Agenda mendatang', Event::public()->where('starts_at', '>=', now())->count(), 'fa-calendar-alt', route('event.index')] : null,
            $user->can('contact-message-view') ? ['Pesan belum dibaca', ContactMessage::whereNull('read_at')->count(), 'fa-envelope', route('contact-message.index')] : null,
            $user->can('user-view') ? ['Pengguna aktif', User::where('is_active', true)->count(), 'fa-users', route('user.index')] : null,
        ]);

        $event = Event::public()->where('starts_at', '>=', now())->oldest('starts_at')->first();

        return view('dashboard.home.index', compact('user', 'cards', 'event'));
    }
}
