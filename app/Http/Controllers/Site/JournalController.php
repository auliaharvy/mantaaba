<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Research;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Jurnal publik (PRD 7, 8.8). Draf tidak tampil & tidak bisa diunduh. */
class JournalController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $journals = Research::published()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('title', 'like', "%{$q}%")
                ->orWhere('authors', 'like', "%{$q}%")->orWhere('keywords', 'like', "%{$q}%")))
            ->when($request->filled('kategori'), fn ($query) => $query->where('category', $request->query('kategori')))
            ->when($request->filled('tahun'), fn ($query) => $query->whereYear('date_of_publish', (int) $request->query('tahun')))
            ->latest('date_of_publish')->paginate(10)->withQueryString();

        return view('site.journal.index', [
            'journals' => $journals,
            'q' => $q,
            'categories' => Research::published()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'years' => Research::published()->selectRaw('YEAR(date_of_publish) as y')->distinct()->orderByDesc('y')->pluck('y'),
            'ejournal' => Setting::current()->url_ejournal,
        ]);
    }

    public function show(string $slug)
    {
        return view('site.journal.show', [
            'journal' => Research::published()->where('slug', $slug)->firstOrFail(),
            'ejournal' => Setting::current()->url_ejournal,
        ]);
    }

    /** Unduhan lewat route penghitung (PRD 8.8). */
    public function download(string $slug)
    {
        $journal = Research::published()->where('slug', $slug)->firstOrFail();
        abort_unless($journal->file && Storage::disk('local')->exists($journal->file), 404);
            Research::withoutTimestamps(fn () => $journal->increment('downloads'));

        return Storage::disk('local')->download($journal->file, Str::slug($journal->title) . '.pdf');
    }
}
