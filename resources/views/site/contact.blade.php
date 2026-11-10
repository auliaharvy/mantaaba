<x-site.layout title="Hubungi Kami" description="Alamat, WhatsApp, dan formulir pesan Rumah Mantaaba Sigaling: pendaftaran, donasi, kemitraan, dan kunjungan.">
    <x-site.breadcrumb-header title="Hubungi Kami" :crumbs="['Kontak']" />
    <div class="container-site grid gap-10 py-12 lg:grid-cols-5">
        <div class="space-y-4 lg:col-span-2">
            <h2 class="font-display text-xl text-brand-primary">{{ $site->site_name }}</h2>
            @if ($site->fullAddress())<p class="flex gap-3"><x-site.icon name="map-pin" class="h-6 w-6 flex-none text-brand-primary" />{{ $site->fullAddress() }}</p>@endif
            @if ($site->office_hours)<p class="flex gap-3"><x-site.icon name="calendar" class="h-6 w-6 flex-none text-brand-primary" />{{ $site->office_hours }}</p>@endif
            @if ($site->phone)<p class="flex gap-3"><x-site.icon name="phone" class="h-6 w-6 flex-none text-brand-primary" />{{ $site->phone }}</p>@endif
            @if ($site->whatsappUrl())<p class="flex gap-3"><x-site.icon name="chat" class="h-6 w-6 flex-none text-brand-primary" /><a href="{{ $site->whatsappUrl() }}" target="_blank" rel="noopener" class="text-brand-accent underline">WhatsApp {{ $site->whatsapp }}</a></p>@endif
            @if ($site->email)<p class="flex gap-3"><x-site.icon name="mail" class="h-6 w-6 flex-none text-brand-primary" /><a href="mailto:{{ $site->email }}" class="break-all text-brand-accent underline">{{ $site->email }}</a></p>@endif
            @if ($site->maps_embed)
                <iframe src="{{ $site->maps_embed }}" title="Peta lokasi Rumah Mantaaba Sigaling" class="mt-4 h-64 w-full rounded-lg border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            @endif
        </div>
        <div class="lg:col-span-3">
            @if (session('success'))<x-site.alert class="mb-6">{{ session('success') }}</x-site.alert>@endif
            @if ($errors->any())<x-site.alert type="error" class="mb-6">Periksa kembali isian yang ditandai.</x-site.alert>@endif
            <form method="POST" action="{{ route('site.contact.send') }}" class="grid gap-5 rounded-site border border-brand-border bg-white p-6 shadow-sm sm:grid-cols-2" novalidate>
                @csrf
                {{-- honeypot: disembunyikan dari manusia --}}
                <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                @foreach ([['name', 'Nama lengkap', 'text', true], ['email', 'Email', 'email', true], ['phone', 'Telepon / WhatsApp', 'tel', false], ['city', 'Kota/Kabupaten', 'text', false]] as [$field, $label, $type, $required])
                    <div>
                        <label for="c-{{ $field }}" class="mb-1 block text-sm font-semibold">{{ $label }}@if ($required)<span class="text-red-700"> *</span>@endif</label>
                        <input id="c-{{ $field }}" type="{{ $type }}" name="{{ $field }}" value="{{ old($field) }}" @required($required)
                            class="w-full rounded-md border-brand-border focus:border-brand-primary focus:ring-brand-primary @error($field) border-red-600 @enderror" @error($field) aria-invalid="true" aria-describedby="e-{{ $field }}" @enderror>
                        @error($field)<p id="e-{{ $field }}" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                @endforeach
                <div class="sm:col-span-2">
                    <label for="c-subject" class="mb-1 block text-sm font-semibold">Perihal<span class="text-red-700"> *</span></label>
                    <select id="c-subject" name="subject" required class="w-full rounded-md border-brand-border focus:border-brand-primary focus:ring-brand-primary">
                        @foreach (App\Models\ContactMessage::SUBJECTS as $subject)<option @selected(old('subject') === $subject)>{{ $subject }}</option>@endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="c-message" class="mb-1 block text-sm font-semibold">Pesan<span class="text-red-700"> *</span></label>
                    <textarea id="c-message" name="message" rows="6" required class="w-full rounded-md border-brand-border focus:border-brand-primary focus:ring-brand-primary @error('message') border-red-600 @enderror">{{ old('message') }}</textarea>
                    @error('message')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2"><x-site.button>Kirim Pesan</x-site.button></div>
            </form>
        </div>
    </div>
</x-site.layout>
