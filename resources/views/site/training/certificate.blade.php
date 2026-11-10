<x-site.layout title="Verifikasi Sertifikat" noindex>
    <x-site.breadcrumb-header title="Verifikasi Sertifikat" :crumbs="['Verifikasi Sertifikat']" />
    <div class="container-site py-12">
        <div class="mx-auto max-w-xl rounded-site border-2 {{ $certificate->isValid() ? 'border-green-600' : 'border-red-600' }} bg-white p-6 shadow-sm">
            <p class="mb-4 inline-block rounded px-3 py-1 text-sm font-bold {{ $certificate->isValid() ? 'bg-green-100 text-green-900' : 'bg-red-100 text-red-900' }}">
                {{ $certificate->isValid() ? '✓ Sertifikat VALID' : '✕ Sertifikat DICABUT' }}</p>
            <dl class="space-y-3">
                <div><dt class="text-sm text-gray-600">Nomor</dt><dd class="font-semibold">{{ $certificate->number }}</dd></div>
                <div><dt class="text-sm text-gray-600">Nama</dt><dd class="text-xl font-bold text-brand-heading">{{ $certificate->member->name }}</dd></div>
                <div><dt class="text-sm text-gray-600">Program</dt><dd class="font-semibold">{{ $certificate->title() }}</dd></div>
                @if ($certificate->result)<div><dt class="text-sm text-gray-600">Predikat</dt><dd>{{ $certificate->result->predicate }}</dd></div>@endif
                @if ($certificate->member->batch)<div><dt class="text-sm text-gray-600">Angkatan</dt><dd>{{ $certificate->member->batch->name }}</dd></div>@endif
                <div><dt class="text-sm text-gray-600">Tanggal terbit</dt><dd>{{ $certificate->issued_at->translatedFormat('j F Y') }}</dd></div>
                @if ($certificate->revoked_at)<div><dt class="text-sm text-gray-600">Dicabut</dt><dd>{{ $certificate->revoked_at->translatedFormat('j F Y') }}</dd></div>@endif
                <div><dt class="text-sm text-gray-600">Penerbit</dt><dd>{{ App\Models\Setting::current()->site_name }} · {{ App\Models\Setting::current()->foundation_name }}</dd></div>
            </dl>
        </div>
    </div>
</x-site.layout>
