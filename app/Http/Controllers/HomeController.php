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
        $open = $user->can('admission-view') ? \App\Models\Batch::whereIn('status', ['open', 'selection'])->latest('id')->first() : null;
        $cards = array_filter([
            $user->can('member-view') ? ['Siswa aktif', \App\Models\Member::where('category', 'siswa')->where('status', 'aktif')->count(), 'fa-user-graduate', route('member.index')] : null,
            $open ? ["Pendaftar {$open->code} (baru)", $open->applicants()->where('status', 'baru')->count(), 'fa-user-plus', route('applicant.index', ['batch' => $open->id])] : null,
            $user->can('news-view') ? ['Berita terbit', Blog::published()->count(), 'fa-newspaper', route('blog.index')] : null,
            $user->can('agenda-view') ? ['Agenda mendatang', Event::public()->where('starts_at', '>=', now())->count(), 'fa-calendar-alt', route('event.index')] : null,
            $user->can('contact-message-view') ? ['Pesan belum dibaca', ContactMessage::whereNull('read_at')->count(), 'fa-envelope', route('contact-message.index')] : null,
            $user->can('user-view') ? ['Pengguna aktif', User::where('is_active', true)->count(), 'fa-users', route('user.index')] : null,
            $user->can('journal-approve') ? ['Voucher menunggu persetujuan', \App\Models\Journal::where('status', 'diajukan')->count(), 'fa-check-double', route('journal.queue')] : null,
            $user->can('donation-verify') ? ['Donasi menunggu verifikasi', \App\Models\Donation::where('status', 'menunggu')->count(), 'fa-hand-holding-heart', route('donation.index')] : null,
            $user->can('stock-view') ? ['Barang stok menipis', \App\Models\Item::lowStock()->count(), 'fa-boxes', route('item.index')] : null,
            $user->can('kitchen-view') ? ['Biaya makan/orang hari ini', $this->mealCost(), 'fa-utensils', route('kitchen.index')] : null,
            $user->can('farm-view') ? ['Panen bulan ini (catatan)', \App\Models\Harvest::where('date', '>=', today()->startOfMonth())->count(), 'fa-seedling', route('farm.index')] : null,
        ]);

        $event = Event::public()->where('starts_at', '>=', now())->oldest('starts_at')->first();

        $announcements = \App\Models\Announcement::for($user)->take(5)->get();
        $todayDuties = $user->member ? $user->member->duties()->with('area')->whereDate('date', today())->get() : collect();

        return view('dashboard.home.index', compact('user', 'cards', 'event', 'announcements', 'todayDuties'));
    }

    private function mealCost(): string
    {
        $day = \App\Services\KitchenService::daily(today()->toDateString(), today()->toDateString())->first();

        return $day['per_person'] !== null ? \App\Support\Money::format(\App\Support\Money::decimal($day['per_person'])) : '-';
    }
}
