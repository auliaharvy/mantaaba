{{-- Header situs: menu dari Menu Manager (PRD 9.1.2), maks. 2 tingkat. --}}
@props(['site'])
@php($menu = App\Models\NavMenu::tree('header'))
<header x-data="{ open: false }" class="sticky top-0 z-40 border-b-2 border-brand-gold/60 bg-white/95 backdrop-blur">
    <div class="container-site flex h-16 items-center justify-between gap-4 lg:h-20">
        <a href="{{ route('site.home') }}" class="flex min-w-0 flex-none items-center gap-3" aria-label="{{ $site->site_name }} — Beranda">
            <img src="{{ $site->imageUrl('logo') }}" alt="" class="h-11 w-11 rounded-full object-cover lg:h-14 lg:w-14">
            <span class="hidden font-display text-sm font-bold uppercase leading-tight tracking-wide text-brand-primary sm:block lg:text-base xl:hidden 2xl:block">{{ $site->site_name }}</span>
        </a>

        <nav class="hidden xl:block" aria-label="Menu utama">
            <ul class="flex items-center gap-1">
                @foreach ($menu as $item)
                    @php($current = $item->isCurrent() || $item->children->contains(fn ($c) => $c->isCurrent()))
                    <li class="relative" @if ($item->children->isNotEmpty()) x-data="{ sub: false }" @mouseenter="sub = true" @mouseleave="sub = false" @keydown.escape="sub = false" @endif>
                        @if ($item->children->isNotEmpty())
                            <button type="button" @click="sub = !sub" :aria-expanded="sub.toString()" aria-haspopup="true"
                                class="inline-flex items-center gap-1 rounded px-3 py-2 text-sm font-semibold {{ $current ? 'text-brand-accent' : 'text-brand-heading hover:text-brand-accent' }}">
                                {{ $item->label }} <x-site.icon name="chevron-down" class="h-4 w-4" />
                            </button>
                            <ul x-show="sub" x-cloak x-transition.opacity @focusout="if (!$el.contains($event.relatedTarget)) sub = false"
                                class="absolute left-0 top-full w-60 rounded-site border border-brand-border bg-white py-2 shadow-lg">
                                @foreach ($item->children as $child)
                                    <li><a href="{{ $child->href() }}" @if ($child->open_new_tab) target="_blank" rel="noopener" @endif class="block px-4 py-2 text-sm {{ $child->isCurrent() ? 'font-bold text-brand-accent' : 'text-gray-700 hover:bg-brand-section-bg hover:text-brand-accent' }}">{{ $child->label }}</a></li>
                                @endforeach
                            </ul>
                        @else
                            <a href="{{ $item->href() }}" @if ($current) aria-current="page" @endif @if ($item->open_new_tab) target="_blank" rel="noopener" @endif
                                class="rounded px-3 py-2 text-sm font-semibold {{ $current ? 'text-brand-accent' : 'text-brand-heading hover:text-brand-accent' }}">{{ $item->label }}</a>
                        @endif
                    </li>
                @endforeach
                <li class="ml-2"><x-site.button :href="route('site.admission')" size="sm">Daftar</x-site.button></li>
            </ul>
        </nav>

        <button type="button" class="rounded p-2 text-brand-heading xl:hidden" @click="open = !open" :aria-expanded="open.toString()" aria-controls="menu-mobile" aria-label="Buka menu">
            <x-site.icon name="menu" class="h-7 w-7" x-show="!open" />
            <x-site.icon name="x" class="h-7 w-7" x-show="open" x-cloak />
        </button>
    </div>

    <nav id="menu-mobile" x-show="open" x-cloak x-transition class="max-h-[calc(100vh-4rem)] overflow-y-auto border-t border-brand-border bg-white xl:hidden" aria-label="Menu utama (seluler)">
        <ul class="container-site py-3">
            @foreach ($menu as $item)
                <li class="border-b border-brand-border last:border-0">
                    @if ($item->children->isNotEmpty())
                        <details @if ($item->children->contains(fn ($c) => $c->isCurrent())) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between py-3 font-semibold text-brand-heading">{{ $item->label }} <x-site.icon name="chevron-down" class="h-4 w-4" /></summary>
                            <ul class="pb-2 pl-4">
                                @foreach ($item->children as $child)
                                    <li><a href="{{ $child->href() }}" class="block py-2 text-gray-700">{{ $child->label }}</a></li>
                                @endforeach
                            </ul>
                        </details>
                    @else
                        <a href="{{ $item->href() }}" class="block py-3 font-semibold {{ $item->isCurrent() ? 'text-brand-accent' : 'text-brand-heading' }}">{{ $item->label }}</a>
                    @endif
                </li>
            @endforeach
            <li class="flex flex-col gap-2 pt-4">
                <x-site.button :href="route('site.admission')">Daftar</x-site.button>
                @auth
                    <x-site.button :href="route('home')" variant="outline">Dashboard</x-site.button>
                @else
                    <x-site.button :href="route('login')" variant="outline">Masuk</x-site.button>
                @endauth
            </li>
        </ul>
    </nav>
</header>
