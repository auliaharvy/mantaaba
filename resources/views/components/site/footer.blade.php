{{-- Footer: kontak dari Setting, tautan dari menu footer (PRD 9.1.2). --}}
@props(['site'])
@php($links = App\Models\NavMenu::tree('footer'))
<footer class="bg-brand-primary-hover text-sm text-white/80">
    <x-site.ornament-divider class="text-brand-gold/70" />
    <div class="container-site grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <div class="mb-4 flex items-center gap-3">
                <img src="{{ $site->logo_footer ? $site->imageUrl('logo_footer') : $site->imageUrl('logo') }}" alt="" class="h-14 w-14 rounded-full object-cover">
                <p class="font-display font-bold uppercase leading-tight tracking-wide text-white">{{ $site->site_name }}</p>
            </div>
            <p>{{ $site->footer_text ?: $site->tagline }}</p>
            <div class="mt-4 flex gap-4"><x-site.social :site="$site" /></div>
        </div>
        <div>
            <h2 class="mb-4 font-display text-base text-brand-gold">Kontak</h2>
            <ul class="space-y-3">
                @if ($site->fullAddress())<li class="flex gap-2"><x-site.icon name="map-pin" class="h-5 w-5 flex-none" />{{ $site->fullAddress() }}</li>@endif
                @if ($site->phone)<li class="flex gap-2"><x-site.icon name="phone" class="h-5 w-5 flex-none" />{{ $site->phone }}</li>@endif
                @if ($site->whatsappUrl())<li class="flex gap-2"><x-site.icon name="chat" class="h-5 w-5 flex-none" /><a href="{{ $site->whatsappUrl() }}" class="hover:text-white" target="_blank" rel="noopener">WhatsApp {{ $site->whatsapp }}</a></li>@endif
                @if ($site->email)<li class="flex gap-2"><x-site.icon name="mail" class="h-5 w-5 flex-none" /><a href="mailto:{{ $site->email }}" class="break-all hover:text-white">{{ $site->email }}</a></li>@endif
                @if ($site->office_hours)<li class="flex gap-2"><x-site.icon name="calendar" class="h-5 w-5 flex-none" />{{ $site->office_hours }}</li>@endif
            </ul>
        </div>
        <div>
            <h2 class="mb-4 font-display text-base text-brand-gold">Tautan</h2>
            <ul class="space-y-2">
                @foreach ($links as $link)
                    <li><a href="{{ $link->href() }}" @if ($link->open_new_tab) target="_blank" rel="noopener" @endif class="hover:text-white">{{ $link->label }}</a></li>
                @endforeach
            </ul>
        </div>
        <div>
            <h2 class="mb-4 font-display text-base text-brand-gold">Dukung Kami</h2>
            <p class="mb-4">Seluruh pendidikan, asrama, dan konsumsi siswa gratis — dibiayai ekosistem dan donatur.</p>
            <div class="flex flex-wrap gap-2">
                <x-site.button :href="route('site.donation')" variant="gold" size="sm">Donasi</x-site.button>
                <x-site.button :href="route('site.admission')" variant="ghost-light" size="sm">Pendaftaran</x-site.button>
            </div>
        </div>
    </div>
    <div class="border-t border-brand-footer-border">
        <div class="container-site flex flex-col gap-2 py-5 text-xs sm:flex-row sm:justify-between">
            <p>&copy; {{ date('Y') }} {{ $site->foundation_name }} · {{ $site->site_name }}</p>
            <p><a href="{{ route('site.page', 'kebijakan-privasi') }}" class="hover:text-white">Kebijakan Privasi</a></p>
        </div>
    </div>
</footer>
