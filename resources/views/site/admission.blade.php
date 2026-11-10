<x-site.layout title="Pendaftaran" description="Pendaftaran siswa Rumah Mantaaba Sigaling: program inkubasi karakter & vokasi bisnis 6 bulan, berasrama, gratis.">
    <x-site.breadcrumb-header title="Pendaftaran Siswa" :crumbs="['Pendaftaran']" subtitle="Inkubasi karakter & vokasi bisnis 6 bulan, berasrama, seluruh biaya ditanggung. Penerimaan dibuka per angkatan." />
    <div class="container-site grid gap-10 py-12 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-site.section-heading title="Persyaratan" />
            @if ($site->admission_requirements)
                <div class="prose-cms">{!! $site->admission_requirements !!}</div>
            @else
                <ul class="space-y-3">
                    @foreach (['Lulusan SMA/sederajat atau seusia, usia produktif.', 'Bersedia menetap di asrama dengan persetujuan orang tua/wali.', 'Bersedia menandatangani pakta integritas dan tata tertib.', 'Prioritas: pemuda dari lingkungan sekitar, keluarga kurang mampu, dan anak yatim.'] as $rule)
                        <li class="flex gap-3"><x-site.icon name="check" class="h-5 w-5 flex-none text-brand-accent" />{{ $rule }}</li>
                    @endforeach
                </ul>
            @endif
            @if ($site->parent_consent_template || $site->integrity_pact_template)
                <h2 class="mb-3 mt-10 font-display text-xl text-brand-primary">Unduh formulir</h2>
                <div class="flex flex-wrap gap-3">
                    @if ($site->parent_consent_template)<x-site.button :href="Storage::disk('public')->url($site->parent_consent_template)" variant="outline" size="sm"><x-site.icon name="download" class="h-4 w-4" /> Surat persetujuan orang tua</x-site.button>@endif
                    @if ($site->integrity_pact_template)<x-site.button :href="Storage::disk('public')->url($site->integrity_pact_template)" variant="outline" size="sm"><x-site.icon name="download" class="h-4 w-4" /> Pakta integritas</x-site.button>@endif
                </div>
            @endif
        </div>
        <aside class="self-start rounded-site border border-brand-border bg-brand-section-bg p-6">
            {{-- Formulir online & info angkatan dibuat di M2 --}}
            <h2 class="font-display text-xl text-brand-primary">Formulir online segera dibuka</h2>
            <p class="mt-2 text-gray-700">Ikuti informasi angkatan berikutnya atau tanyakan langsung kepada pengurus.</p>
            <div class="mt-5 flex flex-col gap-2">
                @if ($wa = $site->whatsappUrl('Assalamualaikum, saya ingin bertanya tentang pendaftaran Rumah Mantaaba Sigaling.'))
                    <x-site.button :href="$wa" target="_blank" rel="noopener">Tanya via WhatsApp</x-site.button>
                @endif
                <x-site.button :href="route('site.contact')" variant="outline">Kirim Pesan</x-site.button>
            </div>
        </aside>
    </div>
</x-site.layout>
