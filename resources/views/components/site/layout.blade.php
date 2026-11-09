{{-- Layout situs publik + SEO (PRD 6, 8.5): title, meta description, OpenGraph, canonical. --}}
@props(['title' => null, 'description' => null, 'image' => null, 'canonical' => null, 'type' => 'website'])
@php
    $site = App\Models\Setting::current();
    $fullTitle = $title ? "{$title} | {$site->site_name}" : $site->site_name . ($site->tagline ? " — {$site->tagline}" : '');
    $description = Str::limit(strip_tags($description ?? $site->tagline ?? 'Website resmi Pengurus Daerah Asosiasi Konsultan Pajak Publik Indonesia (AKP2I) Jawa Barat.'), 160);
    $canonical ??= url()->current();
    $image = url($image ?? $site->imageUrl('logo')); // OG butuh URL absolut (preview WhatsApp)
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:site_name" content="{{ $site->site_name }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="{{ $title ?? $site->site_name }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $image }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="{{ $site->imageUrl('favicon', 'media/logos/akp2i-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-3">Langsung ke konten</a>
    <x-site.topbar :site="$site" />
    <x-site.header :site="$site" />
    <main id="konten" class="flex-1">
        {{ $slot }}
    </main>
    <x-site.footer :site="$site" />
</body>
</html>
