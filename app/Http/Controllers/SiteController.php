<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BusinessUnit;
use App\Models\ContactMessage;
use App\Models\Document;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\Setting;
use App\Notifications\ContactMessageReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Situs publik (PRD 8). Hanya data publik: tidak ada data pribadi anggota (PRD 10). */
class SiteController extends Controller
{
    public function curriculum()
    {
        // data skema & modul dibuat di M3; sementara empty state
        return view('site.curriculum');
    }

    public function units()
    {
        return view('site.units.index', ['units' => BusinessUnit::public()->get()]);
    }

    public function unit(string $slug)
    {
        $unit = BusinessUnit::where('is_public', true)->where('slug', $slug)->with(['gallery' => fn ($q) => $q->published()->with('photos')])->firstOrFail();

        return view('site.units.show', compact('unit'));
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
        $documents = Document::published()->visibleTo(auth()->user())->orderBy('sort_order')->orderByDesc('published_at')->get()
            ->groupBy('category')->sortBy(fn ($_, $category) => array_search($category, Document::CATEGORIES));

        return view('site.documents', compact('documents'));
    }

    /** PRD 9.1.4: file dokumen tidak punya URL publik; dokumen `anggota` hanya untuk pengguna login. */
    public function downloadDocument(Document $document)
    {
        $staff = auth()->user()?->can('cms-view');
        abort_unless($staff || $document->published_at?->isPast(), 404);
        if ($document->visibility === 'anggota' && ! auth()->check()) {
            return redirect()->guest(route('login'));
        }
        abort_unless(Storage::disk('local')->exists($document->file), 404);

        return Storage::disk('local')->response($document->file, Str::slug($document->title) . '.pdf');
    }

    public function admission()
    {
        // formulir & angkatan dibuat di M2
        return view('site.admission', ['site' => Setting::current()]);
    }

    public function donation()
    {
        // konfirmasi donasi & ringkasan transparansi di M5b
        return view('site.donation', ['site' => Setting::current()]);
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

        return redirect()->route('site.contact')->with('success', 'Terima kasih, pesan Anda sudah kami terima. Pengurus akan membalas melalui email atau WhatsApp.');
    }

    public function sitemap()
    {
        $urls = Page::where('is_published', true)->where('show_in_sitemap', true)->get(['slug', 'updated_at'])
            ->map(fn ($p) => ['loc' => $p->url(), 'lastmod' => $p->updated_at])
            ->merge(collect(['site.curriculum', 'site.units', 'site.news', 'site.training', 'site.gallery', 'site.documents', 'site.admission', 'site.donation', 'site.contact'])
                ->map(fn ($route) => ['loc' => route($route), 'lastmod' => null]))
            ->merge(BusinessUnit::public()->get(['slug', 'updated_at'])->map(fn ($u) => ['loc' => route('site.units.show', $u->slug), 'lastmod' => $u->updated_at]))
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
