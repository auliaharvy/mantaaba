<x-site.layout title="Kurikulum" description="Lima skema kompetensi inkubasi 6 bulan Rumah Mantaaba Sigaling: Fundamental, SIM, SIA, SIP, dan Teknologi Bisnis.">
    <x-site.breadcrumb-header title="Kurikulum" :crumbs="['Kurikulum']" subtitle="Lima skema kompetensi berbasis SKKNI yang dipadukan dengan pembinaan karakter Islami." />
    <div class="container-site py-12">
        {{-- Data skema, modul, dan matriks karakter dikelola di modul Pembelajaran (M3). --}}
        <x-site.empty-state title="Rincian kurikulum segera tersedia" icon="academic-cap">
            Skema dan modul pembelajaran sedang disiapkan. Hubungi kami untuk informasi program.
            <div class="mt-4"><x-site.button :href="route('site.contact')" size="sm">Hubungi Kami</x-site.button></div>
        </x-site.empty-state>
    </div>
</x-site.layout>
