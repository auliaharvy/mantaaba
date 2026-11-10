@php($open = $event->registrationOpen())
<x-site.layout :title="$event->title" :description="Str::limit(trim(strip_tags($event->body)), 160)" type="article"
    :image="$event->thumbnail ? Storage::disk('public')->url($event->thumbnail) : null">
    <x-site.breadcrumb-header :title="$event->title" :crumbs="['Agenda' => route('site.training'), Str::limit($event->title, 40)]" />
    <div class="container-site grid gap-10 py-12 lg:grid-cols-3">
        <article class="lg:col-span-2">
            @if ($event->thumbnail)<img src="{{ Storage::disk('public')->url($event->thumbnail) }}" alt="Poster {{ $event->title }}" class="mb-8 w-full rounded-lg">@endif
            <div class="prose-cms">{!! $event->body !!}</div>
        </article>
        <aside>
            <div class="sticky top-24 rounded-lg border border-brand-border bg-white p-6 shadow-sm">
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-gray-600">Jenis</dt><dd class="font-semibold">{{ App\Models\Event::TYPES[$event->type] }}{{ $event->skp_hours ? ' · ' . rtrim(rtrim(number_format($event->skp_hours, 2, ',', '.'), '0'), ',') . ' jam SKP' : '' }}</dd></div>
                    <div><dt class="text-gray-600">Jadwal</dt><dd class="font-semibold">{{ $event->starts_at?->translatedFormat('l, j F Y H:i') }} – {{ $event->ends_at?->isSameDay($event->starts_at) ? $event->ends_at->translatedFormat('H:i') : $event->ends_at?->translatedFormat('j F Y H:i') }}</dd></div>
                    <div><dt class="text-gray-600">Tempat</dt><dd class="font-semibold">{{ App\Models\Event::MODES[$event->mode] }}{{ $event->location ? ' · ' . $event->location : '' }}</dd></div>
                    <div><dt class="text-gray-600">Biaya</dt><dd class="font-semibold">
                        @if ($event->isFree()) Gratis
                        @else Anggota aktif: Rp{{ number_format($event->fee_member, 0, ',', '.') }}<br>Umum: Rp{{ number_format($event->fee_public, 0, ',', '.') }} @endif</dd></div>
                    <div><dt class="text-gray-600">Peserta</dt><dd class="font-semibold">{{ App\Models\Event::AUDIENCES[$event->audience] }}{{ $seatsLeft !== null ? ' · sisa kuota ' . $seatsLeft : '' }}</dd></div>
                    @if ($event->registration_closes_at)<div><dt class="text-gray-600">Pendaftaran ditutup</dt><dd class="font-semibold">{{ $event->registration_closes_at->translatedFormat('j F Y H:i') }}</dd></div>@endif
                </dl>
                <div class="mt-6 border-t border-brand-border pt-6">
                    @error('event')<x-site.alert type="error" class="mb-4">{{ $message }}</x-site.alert>@enderror
                    @if ($registration && in_array($registration->status, ['pending', 'verified']))
                        <x-site.alert class="mb-3">Anda terdaftar: {{ App\Models\Participant::STATUSES[$registration->status] }}.</x-site.alert>
                        <x-site.button :href="route('my-training.show', $registration)" class="w-full">Lihat tiket / pembayaran</x-site.button>
                    @elseif (! $open)
                        <p class="text-sm font-semibold text-gray-700">{{ $event->status === 'done' ? 'Event telah selesai.' : 'Pendaftaran sedang tidak dibuka.' }}</p>
                    @elseif ($seatsLeft === 0)
                        <p class="text-sm font-bold text-red-700">Kuota penuh.</p>
                    @elseif (! auth()->check())
                        <x-site.button :href="route('login')" class="w-full">Masuk untuk mendaftar</x-site.button>
                        <p class="mt-3 text-center text-sm">Belum punya akun? <a href="{{ route('site.contact') }}" class="font-semibold text-brand-accent underline">Hubungi pengurus</a></p>
                    @elseif ($event->audience === 'anggota' && ! auth()->user()->hasRole('anggota'))
                        <p class="text-sm font-semibold text-gray-700">Agenda ini khusus anggota Rumah Mantaaba Sigaling.</p>
                    @else
                        <form method="POST" action="{{ route('site.training.register', $event->slug) }}">
                            @csrf
                            <p class="mb-3 text-sm">Biaya untuk Anda: <strong>{{ (float) $fee === 0.0 ? 'Gratis' : 'Rp' . number_format($fee, 0, ',', '.') }}</strong></p>
                            <x-site.button class="w-full">Daftar Sekarang</x-site.button>
                        </form>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</x-site.layout>
