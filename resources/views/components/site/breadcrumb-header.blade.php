@props(['title', 'crumbs' => []])
<section class="bg-brand-heading text-white">
    <div class="container-site py-10 sm:py-14">
        <nav aria-label="Breadcrumb" class="mb-3 text-sm text-white/80">
            <ol class="flex flex-wrap items-center gap-1">
                <li><a href="{{ route('site.home') }}" class="hover:text-white">Beranda</a></li>
                @foreach ($crumbs as $label => $url)
                    <li aria-hidden="true">›</li>
                    <li>@if (is_string($label))<a href="{{ $url }}" class="hover:text-white">{{ $label }}</a>@else<span aria-current="page">{{ $url }}</span>@endif</li>
                @endforeach
            </ol>
        </nav>
        <h1 class="text-3xl text-white sm:text-4xl">{{ $title }}</h1>
    </div>
</section>
