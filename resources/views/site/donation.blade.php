<x-site.layout title="Donasi" description="Dukung pendidikan gratis Rumah Mantaaba Sigaling: asrama, konsumsi, dan pembelajaran siswa dibiayai ekosistem dan donatur.">
    <x-site.breadcrumb-header title="Donasi" :crumbs="['Donasi']" subtitle="Seluruh pendidikan, asrama, kesehatan, dan konsumsi siswa gratis. Setiap donasi dicatat dan dilaporkan secara terbuka." />
    <div class="container-site grid gap-10 py-12 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-site.section-heading title="Rekening donasi" />
            @forelse ($site->bank_accounts ?? [] as $account)
                <div class="rounded-site border border-brand-border bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-brand-accent">{{ $account['bank'] }}</p>
                    <p class="mt-1 font-display text-2xl tracking-wider text-brand-primary">{{ $account['number'] }}</p>
                    <p class="text-sm text-gray-700">a.n. {{ $account['holder'] }}</p>
                </div>
            @empty
                <x-site.empty-state title="Informasi rekening segera tersedia" icon="heart">Hubungi kami untuk berdonasi.</x-site.empty-state>
            @endforelse
            @if ($site->donation_note)<p class="whitespace-pre-line text-gray-700">{{ $site->donation_note }}</p>@endif
            <p class="text-sm text-gray-600">Laporan penggunaan dana dipublikasikan di halaman <a href="{{ route('site.documents') }}" class="font-semibold text-brand-accent underline">Dokumen</a>.</p>
        </div>
        <aside class="space-y-5 self-start rounded-site border border-brand-border bg-brand-section-bg p-6 text-center">
            @if ($site->qris_image)
                <img src="{{ $site->imageUrl('qris_image') }}" alt="QRIS donasi {{ $site->site_name }}" class="mx-auto w-full max-w-xs rounded-site bg-white p-2">
            @endif
            {{-- Formulir konfirmasi donasi dibuat di M5b --}}
            <p class="text-gray-700">Sudah transfer? Konfirmasikan agar donasi tercatat.</p>
            @if ($wa = $site->whatsappUrl('Assalamualaikum, saya ingin konfirmasi donasi untuk Rumah Mantaaba Sigaling.'))
                <x-site.button :href="$wa" variant="accent" target="_blank" rel="noopener">Konfirmasi via WhatsApp</x-site.button>
            @endif
        </aside>
    </div>
</x-site.layout>
