<x-site.layout :title="'Tiket: ' . $event->title" noindex>
    <x-site.breadcrumb-header title="Tiket Agenda" :crumbs="['Agenda' => route('site.training'), 'Tiket']" />
    <div class="container-site py-12">
        <div class="mx-auto max-w-md rounded-site border border-brand-border border-t-4 border-t-brand-gold bg-white p-8 text-center shadow-sm">
            @if (session('success'))<x-site.alert class="mb-5">{{ session('success') }}</x-site.alert>@endif
            <p class="text-xs font-bold uppercase tracking-widest text-brand-accent">{{ App\Models\Event::TYPES[$event->type] }}</p>
            <h2 class="mt-1 font-display text-2xl text-brand-primary">{{ $event->title }}</h2>
            <p class="mt-2 text-gray-700">{{ $event->starts_at->translatedFormat('l, j F Y H:i') }}{{ $event->location ? ' · ' . $event->location : '' }}</p>
            <div class="mt-6 inline-block rounded-site border border-brand-border bg-white p-3">{!! $qr !!}</div>
            <p class="mt-3 font-mono text-2xl font-bold tracking-widest">{{ $participant->ticket_code }}</p>
            <p class="mt-1 text-lg font-semibold text-brand-heading">{{ $participant->name() }}</p>
            <p class="mt-4 text-sm text-gray-600">Tunjukkan QR ini kepada panitia saat datang. Simpan halaman ini (tangkapan layar) atau buka tautan di email Anda.</p>
        </div>
    </div>
</x-site.layout>
