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
        $skemas = \App\Support\SiteCache::remember('curriculum', fn () => \App\Models\Skema::ordered()->where('is_public', true)->with('units', 'modules')->get());

        return view('site.curriculum', compact('skemas'));
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

    public function donation()
    {
        $year = now()->year;
        $summary = \App\Support\SiteCache::remember("donation.summary.{$year}", fn () => ReportController::transparency("{$year}-01-01", today()->toDateString()));

        return view('site.donation', ['site' => Setting::current(), 'summary' => $summary, 'year' => $year,
            'banks' => \App\Models\CoaAccount::postable()->where('is_bank', true)->get(['id', 'name'])]);
    }

    /** Konfirmasi donasi publik (PRD 9.15): rate limit + honeypot; bukti transfer privat. */
    public function sendDonation(Request $request)
    {
        if (filled($request->input('website'))) {
            return redirect()->route('site.donation')->with('success', 'Terima kasih, konfirmasi Anda sudah kami terima.');
        }
        $data = $request->validate([
            'donor_name' => 'required_unless:is_anonymous,1|nullable|string|max:100',
            'is_anonymous' => 'nullable|boolean',
            'phone' => 'required|string|max:30|regex:/^[0-9+\-\s]{9,}$/',
            'email' => 'nullable|email|max:255',
            'amount' => 'required|numeric|min:1000|max:999999999999',
            'transferred_at' => 'required|date|before_or_equal:today',
            'coa_bank_account_id' => ['required', Rule::exists('coa_accounts', 'id')->where('is_bank', true)],
            'purpose' => ['required', Rule::in(array_keys(\App\Models\Donation::PURPOSES))],
            'proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'message' => 'nullable|string|max:1000',
        ], ['donor_name.required_unless' => 'Isi nama atau centang "Hamba Allah".', 'coa_bank_account_id.required' => 'Pilih rekening tujuan.'], ['coa_bank_account_id' => 'rekening tujuan', 'transferred_at' => 'tanggal transfer']);
        $anonymous = $request->boolean('is_anonymous');
        $donation = \App\Models\Donation::create([
            'donor_name' => $anonymous && blank($data['donor_name'] ?? null) ? 'Hamba Allah' : $data['donor_name'], 'is_anonymous' => $anonymous,
            'proof' => $request->file('proof')->store('donations', 'local'), 'kind' => 'transfer', 'ip' => $request->ip(),
        ] + collect($data)->except(['donor_name', 'is_anonymous', 'proof'])->all());
        app(\App\Services\DonationService::class)->notifyNew($donation);

        return redirect()->route('site.donation')->with('success', 'Jazakumullahu khairan. Konfirmasi donasi Anda sudah kami terima dan akan diverifikasi bendahara.');
    }

    /** Bukti terima donasi (tautan bertanda tangan di email/WA). */
    public function donationReceipt(string $number)
    {
        $donation = \App\Models\Donation::where('receipt_number', $number)->with('journal')->firstOrFail();
        abort_unless($donation->journal?->status === 'terposting', 404);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.donation-receipt', ['donation' => $donation, 'site' => Setting::current()])->setPaper('a5', 'landscape')
            ->download("bukti-donasi-{$number}.pdf");
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
