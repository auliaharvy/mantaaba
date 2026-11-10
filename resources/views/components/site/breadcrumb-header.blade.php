@props(['title', 'crumbs' => [], 'subtitle' => null])
<section class="relative overflow-hidden bg-brand-primary text-white">
    <div class="container-site relative py-10 sm:py-14">
        <nav aria-label="Breadcrumb" class="mb-3 text-sm text-white/80">
            <ol class="flex flex-wrap items-center gap-1">
                <li><a href="{{ route('site.home') }}" class="hover:text-white">Beranda</a></li>
                @foreach ($crumbs as $label => $url)
                    <li aria-hidden="true">›</li>
                    <li>@if (is_string($label))<a href="{{ $url }}" class="hover:text-white">{{ $label }}</a>@else<span aria-current="page">{{ $url }}</span>@endif</li>
                @endforeach
            </ol>
        </nav>
        <h1 class="font-display text-3xl text-white sm:text-4xl">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-3 max-w-2xl text-white/90">{{ $subtitle }}</p>@endif
        <span class="mt-5 block h-0.5 w-20 bg-brand-gold" aria-hidden="true"></span>
    </div>
</section>
