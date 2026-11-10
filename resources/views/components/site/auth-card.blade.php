{{-- Kartu halaman auth bergaya situs (PRD 3.4): logo, judul, ornamen. --}}
@props(['title', 'subtitle' => null])
@php($site = App\Models\Setting::current())
<x-site.layout :title="$title" noindex>
    <div class="bg-brand-section-bg">
        <div class="container-site flex justify-center py-12 sm:py-16">
            <div class="w-full max-w-md rounded-site border border-brand-border border-t-4 border-t-brand-gold bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-6 text-center">
                    <img src="{{ $site->imageUrl('logo') }}" alt="{{ $site->site_name }}" class="mx-auto h-20 w-20 rounded-full object-cover">
                    <h1 class="mt-4 font-display text-2xl text-brand-primary">{{ $title }}</h1>
                    @if ($subtitle)<p class="mt-2 text-sm text-gray-600">{{ $subtitle }}</p>@endif
                </div>
                @if (session('status'))<x-site.alert class="mb-5">{{ session('status') }}</x-site.alert>@endif
                {{ $slot }}
            </div>
        </div>
    </div>
</x-site.layout>
