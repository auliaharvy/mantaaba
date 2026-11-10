<x-site.layout title="Cek Status Pendaftaran" noindex>
    <x-site.breadcrumb-header title="Cek Status Pendaftaran" :crumbs="['Pendaftaran' => route('site.admission'), 'Status']" />
    <div class="container-site py-12">
        <div class="mx-auto max-w-md">
            @if ($result)
                <div class="mb-6 rounded-site border border-brand-border border-t-4 border-t-brand-gold bg-white p-6 shadow-sm" role="status">
                    <p class="text-xs font-bold uppercase tracking-widest text-brand-accent">{{ $result['number'] }}</p>
                    <p class="mt-1 text-lg font-bold text-brand-heading">{{ $result['name'] }}</p>
                    <p class="mt-3 text-gray-700">{{ $result['message'] }}</p>
                </div>
            @elseif ($result === false)
                <x-site.alert type="error" class="mb-6">Data tidak ditemukan. Periksa kembali nomor pendaftaran dan tanggal lahir.</x-site.alert>
            @endif
            <form method="POST" action="{{ route('site.admission.status') }}" class="rounded-site border border-brand-border bg-white p-6 shadow-sm" novalidate>
                @csrf
                <x-site.input name="registration_number" label="Nomor pendaftaran" required placeholder="PMB-2027-1-001" />
                <x-site.input name="birth_date" label="Tanggal lahir" type="date" required />
                <x-site.button class="w-full">Cek Status</x-site.button>
            </form>
        </div>
    </div>
</x-site.layout>
