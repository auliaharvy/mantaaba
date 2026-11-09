{{-- Menu tetap di kode (PRD 7); isi halaman dari CMS. --}}
@props(['site'])
@php
    $menu = [
        ['label' => 'Beranda', 'route' => 'site.home'],
        ['label' => 'Profil', 'children' => [
            ['label' => 'Profil', 'route' => 'site.profile'],
            ['label' => 'Pengurus', 'route' => 'site.board'],
            ['label' => 'Pengurus Cabang', 'route' => 'site.branches', 'active' => 'site.branch*'],
            ['label' => 'Galeri', 'route' => 'site.gallery', 'active' => 'site.gallery*'],
            ['label' => 'Peraturan Organisasi', 'route' => 'site.documents'],
        ]],
        ['label' => 'Keanggotaan', 'children' => [
            ['label' => 'Tentang Keanggotaan', 'route' => 'site.membership'],
            ['label' => 'Layanan Keanggotaan', 'route' => 'site.membership.services'],
            ['label' => 'Cek Anggota', 'route' => 'site.membership.check'],
        ]],
        ['label' => 'Pusat Pelatihan', 'route' => 'site.training', 'active' => 'site.training*'],
        ['label' => 'Berita', 'route' => 'site.news', 'active' => 'site.news*'],
        ['label' => 'Jurnal', 'route' => 'site.journal', 'active' => 'site.journal*'],
        ['label' => 'Forum', 'route' => 'site.forum', 'active' => 'site.forum*'],
        ['label' => 'Kontak', 'route' => 'site.contact'],
    ];
    $isActive = fn ($item) => isset($item['children'])
        ? collect($item['children'])->contains(fn ($c) => request()->routeIs($c['active'] ?? $c['route']))
        : request()->routeIs($item['active'] ?? $item['route']);
@endphp
<header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-brand-border bg-white/95 backdrop-blur">
    <div class="container-site flex h-16 items-center justify-between gap-4 lg:h-20">
        <a href="{{ route('site.home') }}" class="flex-none" aria-label="{{ $site->site_name }} — Beranda">
            <img src="{{ $site->imageUrl('logo') }}" alt="{{ $site->site_name }}" class="h-10 w-auto lg:h-12">
        </a>

        <nav class="hidden lg:block" aria-label="Menu utama">
            <ul class="flex items-center gap-1">
                @foreach ($menu as $item)
                    <li class="relative" @if (isset($item['children'])) x-data="{ sub: false }" @mouseenter="sub = true" @mouseleave="sub = false" @keydown.escape="sub = false" @endif>
                        @isset($item['children'])
                            <button type="button" @click="sub = !sub" :aria-expanded="sub.toString()" aria-haspopup="true"
                                class="inline-flex items-center gap-1 rounded px-3 py-2 text-sm font-semibold {{ $isActive($item) ? 'text-brand-secondary' : 'text-brand-heading hover:text-brand-secondary' }}">
                                {{ $item['label'] }} <x-site.icon name="chevron-down" class="h-4 w-4" />
                            </button>
                            <ul x-show="sub" x-cloak x-transition.opacity @focusout="if (!$el.contains($event.relatedTarget)) sub = false"
                                class="absolute left-0 top-full w-60 rounded-md border border-brand-border bg-white py-2 shadow-lg">
                                @foreach ($item['children'] as $child)
                                    <li><a href="{{ route($child['route']) }}" class="block px-4 py-2 text-sm {{ request()->routeIs($child['active'] ?? $child['route']) ? 'font-bold text-brand-secondary' : 'text-gray-700 hover:bg-brand-section-bg hover:text-brand-secondary' }}">{{ $child['label'] }}</a></li>
                                @endforeach
                            </ul>
                        @else
                            <a href="{{ route($item['route']) }}" @if ($isActive($item)) aria-current="page" @endif
                                class="rounded px-3 py-2 text-sm font-semibold {{ $isActive($item) ? 'text-brand-secondary' : 'text-brand-heading hover:text-brand-secondary' }}">{{ $item['label'] }}</a>
                        @endisset
                    </li>
                @endforeach
            </ul>
        </nav>

        <button type="button" class="rounded p-2 text-brand-heading lg:hidden" @click="open = !open" :aria-expanded="open.toString()" aria-controls="menu-mobile" aria-label="Buka menu">
            <x-site.icon name="menu" class="h-7 w-7" x-show="!open" />
            <x-site.icon name="x" class="h-7 w-7" x-show="open" x-cloak />
        </button>
    </div>

    <nav id="menu-mobile" x-show="open" x-cloak x-transition class="border-t border-brand-border bg-white lg:hidden" aria-label="Menu utama (seluler)">
        <ul class="container-site py-3">
            @foreach ($menu as $item)
                <li class="border-b border-brand-border last:border-0">
                    @isset($item['children'])
                        <details @if ($isActive($item)) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between py-3 font-semibold text-brand-heading">{{ $item['label'] }} <x-site.icon name="chevron-down" class="h-4 w-4" /></summary>
                            <ul class="pb-2 pl-4">
                                @foreach ($item['children'] as $child)
                                    <li><a href="{{ route($child['route']) }}" class="block py-2 text-gray-700">{{ $child['label'] }}</a></li>
                                @endforeach
                            </ul>
                        </details>
                    @else
                        <a href="{{ route($item['route']) }}" class="block py-3 font-semibold {{ $isActive($item) ? 'text-brand-secondary' : 'text-brand-heading' }}">{{ $item['label'] }}</a>
                    @endisset
                </li>
            @endforeach
            <li class="flex flex-col gap-2 pt-4">
                <x-site.button :href="$site->url_registration" target="_blank" rel="noopener">Pendaftaran Anggota</x-site.button>
                @auth
                    <x-site.button :href="route('home')" variant="outline">Dashboard</x-site.button>
                @else
                    <x-site.button :href="route('login')" variant="outline">Masuk</x-site.button>
                @endauth
            </li>
        </ul>
    </nav>
</header>
