@props(['site'])
<footer class="mt-16 bg-brand-heading text-sm text-white/80">
    <div class="container-site grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <img src="{{ $site->logo_footer ? $site->imageUrl('logo_footer') : $site->imageUrl('logo') }}" alt="{{ $site->site_name }}" class="mb-4 h-12 w-auto {{ $site->logo_footer ? '' : 'rounded bg-white p-1' }}">
            <p>{{ $site->tagline ?? 'Pengurus Daerah Asosiasi Konsultan Pajak Publik Indonesia Jawa Barat.' }}</p>
            <div class="mt-4 flex gap-4"><x-site.social :site="$site" /></div>
        </div>
        <div>
            <h2 class="mb-4 text-base text-white">Sekretariat</h2>
            <ul class="space-y-3">
                @if ($site->address)<li class="flex gap-2"><x-site.icon name="map-pin" class="h-5 w-5 flex-none" />{{ $site->address }}</li>@endif
                @if ($site->phone)<li class="flex gap-2"><x-site.icon name="phone" class="h-5 w-5 flex-none" />{{ $site->phone }}</li>@endif
                @if ($site->whatsappUrl())<li class="flex gap-2"><x-site.icon name="chat" class="h-5 w-5 flex-none" /><a href="{{ $site->whatsappUrl() }}" class="hover:text-white" target="_blank" rel="noopener">WhatsApp {{ $site->whatsapp }}</a></li>@endif
                <li class="flex gap-2"><x-site.icon name="mail" class="h-5 w-5 flex-none" /><a href="mailto:{{ $site->email }}" class="break-all hover:text-white">{{ $site->email }}</a></li>
            </ul>
        </div>
        <div>
            <h2 class="mb-4 text-base text-white">Tautan Cepat</h2>
            <ul class="space-y-2">
                <li><a href="{{ route('site.profile') }}" class="hover:text-white">Profil</a></li>
                <li><a href="{{ route('site.board') }}" class="hover:text-white">Pengurus</a></li>
                <li><a href="{{ route('site.membership') }}" class="hover:text-white">Keanggotaan</a></li>
                <li><a href="{{ route('site.membership.check') }}" class="hover:text-white">Cek Anggota</a></li>
                <li><a href="{{ route('site.training') }}" class="hover:text-white">Pusat Pelatihan</a></li>
                <li><a href="{{ route('site.contact') }}" class="hover:text-white">Hubungi Kami</a></li>
            </ul>
        </div>
        <div>
            <h2 class="mb-4 text-base text-white">AKP2I Pusat & Mitra</h2>
            <ul class="space-y-2">
                <li><a href="{{ config('akp2i.urls.central_site') }}" target="_blank" rel="noopener" class="hover:text-white">akp2i.or.id</a></li>
                <li><a href="{{ $site->url_member_service }}" target="_blank" rel="noopener" class="hover:text-white">Layanan Anggota (akp2i.id)</a></li>
                @if ($site->url_ejournal)<li><a href="{{ $site->url_ejournal }}" target="_blank" rel="noopener" class="hover:text-white">eJurnal AKP2I</a></li>@endif
                <li><a href="https://www.pajak.go.id" target="_blank" rel="noopener" class="hover:text-white">Direktorat Jenderal Pajak</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-brand-footer-border">
        <div class="container-site flex flex-col gap-2 py-5 text-xs sm:flex-row sm:justify-between">
            <p>&copy; {{ date('Y') }} {{ $site->site_name }}. Hak cipta dilindungi.</p>
            <p class="flex gap-4">
                <a href="{{ route('site.privacy') }}" class="hover:text-white">Kebijakan Privasi</a>
                <a href="{{ route('site.terms') }}" class="hover:text-white">Syarat & Ketentuan</a>
            </p>
        </div>
    </div>
</footer>
