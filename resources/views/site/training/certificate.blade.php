@php($participant = $certificate->participant)
<x-site.layout title="Verifikasi Sertifikat">
    <x-site.breadcrumb-header title="Verifikasi Sertifikat" :crumbs="['Verifikasi Sertifikat']" />
    <div class="container-site py-12">
        <div class="mx-auto max-w-xl rounded-lg border-2 {{ $certificate->isValid() ? 'border-green-600' : 'border-red-600' }} bg-white p-6 shadow-sm">
            <p class="mb-4 inline-block rounded px-3 py-1 text-sm font-bold {{ $certificate->isValid() ? 'bg-green-100 text-green-900' : 'bg-red-100 text-red-900' }}">
                {{ $certificate->isValid() ? '✓ Sertifikat VALID' : '✕ Sertifikat DICABUT' }}</p>
            <dl class="space-y-3">
                <div><dt class="text-sm text-gray-600">Nomor</dt><dd class="font-semibold">{{ $certificate->number }}</dd></div>
                <div><dt class="text-sm text-gray-600">Nama peserta</dt><dd class="text-xl font-bold text-brand-heading">{{ $participant->user->name }}</dd></div>
                <div><dt class="text-sm text-gray-600">Event</dt><dd class="font-semibold">{{ $participant->event->title }}</dd></div>
                <div><dt class="text-sm text-gray-600">Tanggal</dt><dd>{{ $participant->event->starts_at?->translatedFormat('j F Y') }}</dd></div>
                <div><dt class="text-sm text-gray-600">Penyelenggara</dt><dd>{{ config('app.name') }}</dd></div>
                @if ($participant->event->skp_hours)<div><dt class="text-sm text-gray-600">Jam SKP</dt><dd>{{ rtrim(rtrim(number_format($participant->event->skp_hours, 2, ',', '.'), '0'), ',') }}</dd></div>@endif
            </dl>
        </div>
    </div>
</x-site.layout>
