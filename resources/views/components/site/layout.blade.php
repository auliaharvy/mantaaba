{{-- Layout situs publik + SEO (PRD 7.1, 8): token warna dari Setting, title, meta description, OpenGraph, canonical. --}}
@props(['title' => null, 'description' => null, 'image' => null, 'canonical' => null, 'type' => 'website', 'noindex' => false])
@php
    $site = App\Models\Setting::current();
    $fullTitle = $title ? "{$title} | {$site->site_name}" : $site->site_name . ($site->tagline ? " — {$site->tagline}" : '');
    $description = Str::limit(trim(strip_tags($description ?? $site->meta_description ?? $site->tagline ?? 'Rumah Mantaaba Sigaling: pembinaan karakter & vokasi bisnis pemuda menuju masyarakat transparan pajak.')), 160);
    $canonical ??= url()->current();
    $image = url($image ?? ($site->og_image ? $site->imageUrl('og_image') : $site->imageUrl('logo'))); // OG butuh URL absolut (preview WhatsApp)
@endphp
<!DOCTYPE html>
<html lang="id" style="{{ $site->cssVariables() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    @if ($noindex)<meta name="robots" content="noindex, nofollow">@endif
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:site_name" content="{{ $site->site_name }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="{{ $title ?? $site->site_name }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $image }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="{{ $site->color_primary }}">
    <link rel="icon" href="{{ $site->imageUrl('favicon', 'media/logos/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('media/logos/favicon-180.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cinzel:wght@500;700&family=Inter:wght@400;500;600;700&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-3">Langsung ke konten</a>
    @if ($site->maintenance_mode && auth()->check())
        <div class="bg-brand-accent px-4 py-2 text-center text-sm font-medium text-white" role="status">
            Mode "Sedang Dibangun" aktif: pengunjung yang belum login melihat halaman under construction.
            @can('update', $site)<a href="{{ route('setting.edit', ['tab' => 'pemeliharaan']) }}" class="ml-1 font-bold underline">Atur</a>@endcan
        </div>
    @endif
    <x-site.topbar :site="$site" />
    <x-site.header :site="$site" />
    <main id="konten" class="flex-1">
        {{ $slot }}
    </main>
    <x-site.footer :site="$site" />
</body>
</html>
