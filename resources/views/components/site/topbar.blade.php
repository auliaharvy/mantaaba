@props(['site'])
<div class="hidden bg-brand-primary-hover text-sm text-white/90 md:block">
    <div class="container-site flex items-center justify-between gap-4 py-2">
        <div class="flex items-center gap-5">
            @if ($site->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $site->phone) }}" class="inline-flex items-center gap-1.5 hover:text-white"><x-site.icon name="phone" class="h-4 w-4" />{{ $site->phone }}</a>@endif
            @if ($site->email)<a href="mailto:{{ $site->email }}" class="inline-flex items-center gap-1.5 hover:text-white"><x-site.icon name="mail" class="h-4 w-4" />{{ $site->email }}</a>@endif
        </div>
        <div class="flex items-center gap-4">
            <x-site.social :site="$site" class="h-4 w-4" />
            <a href="{{ route('site.donation') }}" class="font-semibold text-brand-gold hover:text-white">Donasi</a>
            @auth
                <a href="{{ route('home') }}" class="font-semibold hover:text-white">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="font-semibold hover:text-white">Masuk</a>
            @endauth
        </div>
    </div>
</div>
