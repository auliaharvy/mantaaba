@php($open = $event->registrationOpen())
<x-site.layout :title="$event->title" :description="Str::limit(trim(strip_tags((string) $event->body)), 160)" type="article"
    :image="$event->thumbnail ? Storage::disk('public')->url($event->thumbnail) : null">
    <x-site.breadcrumb-header :title="$event->title" :crumbs="['Agenda' => route('site.training'), Str::limit($event->title, 40)]" />
    <div class="container-site grid gap-10 py-12 lg:grid-cols-3">
        <article class="lg:col-span-2">
            @if ($event->thumbnail)<img src="{{ Storage::disk('public')->url($event->thumbnail) }}" alt="Poster {{ $event->title }}" class="mb-8 w-full rounded-site">@endif
            <div class="prose-cms">{!! $event->body !!}</div>
        </article>
        <aside>
            <div class="sticky top-24 rounded-site border border-brand-border border-t-4 border-t-brand-gold bg-white p-6 shadow-sm">
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-gray-600">Jenis</dt><dd class="font-semibold">{{ App\Models\Event::TYPES[$event->type] }}</dd></div>
                    <div><dt class="text-gray-600">Jadwal</dt><dd class="font-semibold">{{ $event->starts_at->translatedFormat('l, j F Y H:i') }} – {{ $event->ends_at->isSameDay($event->starts_at) ? $event->ends_at->format('H:i') : $event->ends_at->translatedFormat('j F Y H:i') }}</dd></div>
                    <div><dt class="text-gray-600">Tempat</dt><dd class="font-semibold">{{ App\Models\Event::MODES[$event->mode] }}{{ $event->location ? ' · ' . $event->location : '' }}</dd></div>
                    <div><dt class="text-gray-600">Peserta</dt><dd class="font-semibold">{{ $event->audience === 'publik' ? 'Terbuka untuk umum' : 'Internal anggota' }}{{ $seatsLeft !== null && $event->audience === 'publik' ? ' · sisa kuota ' . $seatsLeft : '' }}</dd></div>
                    @if ($event->registration_closes_at && $event->audience === 'publik')<div><dt class="text-gray-600">Pendaftaran ditutup</dt><dd class="font-semibold">{{ $event->registration_closes_at->translatedFormat('j F Y H:i') }}</dd></div>@endif
                </dl>
                <div class="mt-6 border-t border-brand-border pt-6">
                    @error('event')<x-site.alert type="error" class="mb-4">{{ $message }}</x-site.alert>@enderror
                    @if ($registration)
                        <x-site.alert class="mb-3">Anda terdaftar di agenda ini.</x-site.alert>
                        <x-site.button :href="route('my-training.show', $registration)" class="w-full">Lihat tiket</x-site.button>
                    @elseif ($event->audience !== 'publik')
                        <p class="text-sm text-gray-700">Agenda internal: peserta adalah anggota sasaran, tidak perlu mendaftar.</p>
                    @elseif (! $open)
                        <p class="text-sm font-semibold text-gray-700">{{ $event->status === 'done' || $event->ends_at->isPast() ? 'Agenda telah selesai.' : 'Pendaftaran sedang tidak dibuka.' }}</p>
                    @elseif ($seatsLeft === 0)
                        <p class="text-sm font-bold text-red-700">Kuota penuh.</p>
                    @else
                        <form method="POST" action="{{ route('site.training.register', $event->slug) }}" novalidate>
                            @csrf
                            <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                            @guest
                                <x-site.input name="guest_name" label="Nama lengkap" required autocomplete="name" />
                                <x-site.input name="guest_phone" label="No. WhatsApp" type="tel" required autocomplete="tel" />
                                <x-site.input name="guest_email" label="Email (untuk menerima tiket)" type="email" autocomplete="email" />
                            @endguest
                            <x-site.button class="w-full">Daftar Gratis</x-site.button>
                        </form>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</x-site.layout>
