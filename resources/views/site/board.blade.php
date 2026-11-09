<x-site.layout title="Pengurus" description="Susunan Pengurus Daerah AKP2I Jawa Barat periode aktif.">
    <x-site.breadcrumb-header title="Pengurus Daerah" :crumbs="['Profil' => route('site.profile'), 'Pengurus']" />
    <div class="container-site py-12">
        <p class="mb-8 text-gray-600">{{ $organization->name }}{{ $period ? ' · Periode ' . $period->name : '' }}</p>
        @include('site._board')
    </div>
</x-site.layout>
