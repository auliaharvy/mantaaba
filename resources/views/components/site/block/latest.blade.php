@props(['data', 'alt' => false, 'first' => false])
@php
    $news = ($data['source'] ?? 'berita') === 'berita';
    $count = (int) ($data['count'] ?? 3);
    $items = $news
        ? App\Models\Blog::published()->with('user', 'category')->latest('published_at')->take($count)->get()
        : App\Models\Event::public()->where('status', '!=', 'done')->where('ends_at', '>=', now())->oldest('starts_at')->take($count)->get();
@endphp
<section class="py-14 {{ $alt ? 'bg-brand-section-bg' : '' }}">
    <div class="container-site">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <x-site.section-heading :title="$data['title'] ?: ($news ? 'Kabar Terbaru' : 'Agenda Mendatang')" class="mb-0" />
            <a href="{{ route($news ? 'site.news' : 'site.training') }}" class="inline-flex items-center gap-1 font-semibold text-brand-accent">{{ $news ? 'Semua berita' : 'Semua agenda' }} <x-site.icon name="arrow-right" class="h-4 w-4" /></a>
        </div>
        @if ($items->isEmpty())
            <x-site.empty-state :title="$news ? 'Belum ada berita' : 'Belum ada agenda terdekat'" :icon="$news ? 'document' : 'calendar'" class="mt-8" />
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($items as $item)
                    @if ($news)<x-site.card-post :blog="$item" />@else<x-site.card-event :event="$item" />@endif
                @endforeach
            </div>
        @endif
    </div>
</section>
