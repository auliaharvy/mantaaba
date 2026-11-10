<x-site.layout title="Pendaftaran terkirim" noindex>
    <x-site.breadcrumb-header title="Pendaftaran Terkirim" :crumbs="['Pendaftaran' => route('site.admission'), 'Terkirim']" />
    <div class="container-site py-12">
        <div class="mx-auto max-w-xl rounded-site border border-brand-border border-t-4 border-t-brand-gold bg-white p-8 text-center shadow-sm">
            <x-site.icon name="check" class="mx-auto h-12 w-12 text-green-700" />
            <h2 class="mt-3 font-display text-2xl text-brand-primary">Terima kasih, {{ $applicant->name }}</h2>
            <p class="mt-2 text-gray-700">Pendaftaran Anda untuk {{ $applicant->batch->name }} sudah kami terima. Simpan nomor berikut untuk cek status.</p>
            <p class="mt-6 text-xs font-bold uppercase tracking-widest text-brand-accent">Nomor pendaftaran</p>
            <p class="font-display text-3xl tracking-wider text-brand-primary">{{ $applicant->registration_number }}</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <x-site.button :href="$receiptUrl"><x-site.icon name="download" class="h-4 w-4" /> Simpan bukti (PDF)</x-site.button>
                @if ($wa = $site->whatsappUrl("Assalamualaikum, saya sudah mendaftar dengan nomor {$applicant->registration_number} atas nama {$applicant->name}."))
                    <x-site.button :href="$wa" variant="outline" target="_blank" rel="noopener">Konfirmasi via WhatsApp</x-site.button>
                @endif
            </div>
            <p class="mt-6 text-sm text-gray-600">Cek perkembangan di <a href="{{ route('site.admission.status') }}" class="font-semibold text-brand-accent underline">halaman status</a> dengan nomor pendaftaran dan tanggal lahir.</p>
        </div>
    </div>
</x-site.layout>
