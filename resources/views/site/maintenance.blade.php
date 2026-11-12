{{-- Halaman "sedang dibangun" (SiteUnderConstruction): mandiri, tanpa menu situs; diatur di Setting Situs → Mode Dibangun. --}}
@php
    $title = $site->maintenance_title ?: 'Situs sedang dalam pembangunan';
    $message = $site->maintenance_message ?: 'Kami sedang menyiapkan wajah baru ' . $site->site_name . ' agar lebih mudah dikenal, didukung, dan diikuti kegiatannya. Terima kasih atas kesabaran Anda.';
    $photo = Storage::disk('public')->exists('pages/blocks/bangunan_mantaaba_2.webp') ? Storage::disk('public')->url('pages/blocks/bangunan_mantaaba_2.webp') : null;
    $wa = $site->whatsappUrl('Assalamualaikum, saya ingin bertanya tentang ' . $site->site_name . '.');
@endphp
<!DOCTYPE html>
<html lang="id" style="{{ $site->cssVariables() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ $site->site_name }}</title>
    <meta name="description" content="{{ Str::limit(strip_tags($message), 160) }}">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="{{ $site->color_primary }}">
    <link rel="icon" href="{{ $site->imageUrl('favicon', 'media/logos/favicon-32.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Inter:wght@400;500;600;700&display=swap">
    @vite(['resources/css/app.css'])
</head>
<body class="relative flex min-h-screen flex-col bg-brand-primary text-white">
    @if ($photo)
        <img src="{{ $photo }}" alt="" class="absolute inset-0 h-full w-full object-cover">
    @endif
    <div class="absolute inset-0 bg-gradient-to-b from-brand-primary/85 via-brand-primary/80 to-brand-primary-hover/95"></div>

    <main class="container-site relative flex flex-1 flex-col items-center justify-center py-16 text-center">
        <img src="{{ $site->imageUrl('logo') }}" alt="{{ $site->site_name }}" class="h-24 w-24 rounded-full object-cover shadow-xl ring-4 ring-brand-gold/60 sm:h-28 sm:w-28">
        <p class="mt-8 text-sm font-semibold uppercase tracking-[0.2em] text-brand-gold">{{ $site->site_name }}</p>
        <h1 class="mt-4 max-w-3xl text-balance font-display text-white text-3xl font-bold leading-tight sm:text-5xl">{{ $title }}</h1>
        <div class="mt-6 flex items-center gap-3 text-brand-gold" aria-hidden="true">
            <span class="h-px w-12 bg-brand-gold/70"></span><span class="h-2 w-2 rotate-45 bg-brand-gold"></span><span class="h-px w-12 bg-brand-gold/70"></span>
        </div>
        <p class="mt-6 max-w-2xl whitespace-pre-line text-base leading-relaxed text-white/90 sm:text-lg">{{ $message }}</p>

        @if ($site->maintenance_until?->isFuture() || $site->maintenance_until?->isToday())
            <p class="mt-8 inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-5 py-2 text-sm font-medium backdrop-blur">
                <x-site.icon name="calendar" class="h-4 w-4 text-brand-gold" />
                Perkiraan dibuka: {{ $site->maintenance_until->translatedFormat('l, j F Y') }}
            </p>
        @endif

        @if ($wa || $site->email)
            <div class="mt-10 flex w-full max-w-sm flex-col gap-3 sm:max-w-none sm:flex-row sm:justify-center">
                @if ($wa)
                    <a href="{{ $wa }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-site bg-brand-gold px-6 py-3 text-sm font-bold uppercase tracking-wide text-brand-heading hover:bg-white">
                        <x-site.icon name="phone" class="h-4 w-4" /> Hubungi via WhatsApp
                    </a>
                @endif
                @if ($site->email)
                    <a href="mailto:{{ $site->email }}" class="inline-flex items-center justify-center gap-2 rounded-site border-2 border-white/80 px-6 py-3 text-sm font-bold uppercase tracking-wide hover:bg-white hover:text-brand-primary">
                        <x-site.icon name="mail" class="h-4 w-4" /> {{ $site->email }}
                    </a>
                @endif
            </div>
        @endif

        <div class="mt-8 flex items-center gap-5 text-white/80"><x-site.social :site="$site" class="h-6 w-6" /></div>
    </main>

    <footer class="relative border-t border-white/15 py-5 text-sm text-white/70">
        <div class="container-site flex flex-col items-center justify-between gap-2 sm:flex-row">
            <span>&copy; {{ now()->year }} {{ $site->foundation_name }} · {{ $site->site_name }}</span>
            <a href="{{ route('login') }}" class="font-semibold hover:text-white">Masuk pengurus</a>
        </div>
    </footer>
</body>
</html>
