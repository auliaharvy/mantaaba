<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\ContactMessage;
use App\Models\Document;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\GalleryPhoto;
use App\Models\Page;
use App\Models\Setting;
use App\Models\Slider;
use App\Notifications\ContactMessageReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

/** Situs publik (PRD 8). Hanya data publik: tidak ada data pribadi anggota (PRD 10). */
class SiteController extends Controller
{
    /** Slug halaman CMS -> route publik kanonis. */
    public const PAGE_ROUTES = [
        'profil' => 'site.profile',
        'sambutan' => 'site.home',
        'kebijakan-privasi' => 'site.privacy',
    ];

    public function home()
    {
        $data = Cache::remember('site.home', 600, fn () => [
            'events' => Event::public()->where('status', '!=', 'done')->where('ends_at', '>=', now())->oldest('starts_at')->take(3)->get(),
            'news' => Blog::published()->with('user', 'category')->latest('published_at')->take(3)->get(),
            'sliders' => Slider::where('is_active', true)->orderBy('sort_order')->get(),
            'welcome' => Page::where('slug', 'sambutan')->where('is_published', true)->first(),
            'photos' => GalleryPhoto::with('gallery')->whereHas('gallery', fn ($q) => $q->published())->latest('id')->take(6)->get(),
        ]);

        return view('site.home', $data + ['stats' => self::stats()]);
    }

    /** Statistik beranda dari database (PRD 6.2), cache 10 menit. */
    public static function stats(): array
    {
        return Cache::remember('site.stats', 600, fn () => [
            'events' => Event::where('status', 'done')->count(),
            'news' => Blog::published()->count(),
            'galleries' => Gallery::published()->count(),
        ]);
    }

    public function page(string $slug)
    {
        abort_unless(isset(self::PAGE_ROUTES[$slug]), 404);

        return redirect()->route(self::PAGE_ROUTES[$slug]);
    }

    public function showPage(string $slug, array $crumbs = [])
    {
        $page = Page::published($slug);

        return view('site.page', compact('page', 'crumbs'));
    }

    public function profile()
    {
        return $this->showPage('profil', ['Profil']);
    }

    public function privacy()
    {
        return $this->showPage('kebijakan-privasi', ['Kebijakan Privasi']);
    }

    public function galleries()
    {
        $galleries = Gallery::published()->with('photos')->withCount('photos')->latest('published_at')->paginate(12);

        return view('site.galleries', compact('galleries'));
    }

    public function gallery(string $slug)
    {
        $gallery = Gallery::published()->where('slug', $slug)->with('photos')->firstOrFail();

        return view('site.gallery', compact('gallery'));
    }

    public function documents()
    {
        $documents = Document::published()->orderBy('sort_order')->orderByDesc('published_at')->get()
            ->groupBy('category')->sortBy(fn ($_, $category) => array_search($category, Document::CATEGORIES));

        return view('site.documents', compact('documents'));
    }

    public function contact()
    {
        return view('site.contact', ['site' => Setting::current()]);
    }

    public function sendContact(Request $request)
    {
        // honeypot: bot mengisi field tersembunyi -> pura-pura berhasil, tidak disimpan
        if (filled($request->input('website'))) {
            return redirect()->route('site.contact')->with('success', 'Terima kasih, pesan Anda sudah kami terima.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'city' => 'nullable|string|max:100',
            'subject' => ['required', Rule::in(ContactMessage::SUBJECTS)],
            'message' => 'required|string|min:10|max:5000',
        ]);
        $message = ContactMessage::create($data + ['ip' => $request->ip()]);
        Notification::route('mail', Setting::current()->email)->notify(new ContactMessageReceived($message));

        return redirect()->route('site.contact')->with('success', 'Terima kasih, pesan Anda sudah kami terima. Pengurus akan membalas melalui email.');
    }

    public function sitemap()
    {
        $urls = collect(['site.home', 'site.news', 'site.training', 'site.profile', 'site.gallery', 'site.documents', 'site.contact', 'site.privacy'])
            ->map(fn ($route) => ['loc' => route($route), 'lastmod' => null])
            ->merge(Gallery::published()->get()->map(fn ($g) => ['loc' => route('site.gallery.show', $g->slug), 'lastmod' => $g->updated_at]))
            ->merge(Event::public()->get(['slug', 'updated_at'])->map(fn ($e) => ['loc' => route('site.training.show', $e->slug), 'lastmod' => $e->updated_at]))
            ->merge(Blog::published()->get(['slug', 'updated_at'])->map(fn ($b) => ['loc' => route('site.news.show', $b->slug), 'lastmod' => $b->updated_at]));

        return response()->view('site.sitemap', compact('urls'))->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        return response("User-agent: *\nDisallow: /dashboard\nDisallow: /login\n\nSitemap: " . route('site.sitemap') . "\n")
            ->header('Content-Type', 'text/plain');
    }
}
