@props(['title'])
<x-site.layout :title="$title">
    <div class="container-site flex justify-center py-12">
        <div class="w-full max-w-md rounded-lg border border-brand-border bg-white p-6 shadow-sm sm:p-8">
            <h1 class="mb-6 text-2xl">{{ $title }}</h1>
            {{ $slot }}
        </div>
    </div>
</x-site.layout>
