<x-site.layout title="Kurikulum" description="Lima skema kompetensi inkubasi 6 bulan Rumah Mantaaba Sigaling: Fundamental, SIM, SIA, SIP, dan Teknologi Bisnis.">
    <x-site.breadcrumb-header title="Kurikulum" :crumbs="['Kurikulum']" subtitle="Lima skema kompetensi berbasis SKKNI yang dipadukan dengan pembinaan karakter Islami: 'Asabiyah dan Fastabiqul Khairat." />
    <div class="container-site py-12">
        @if ($skemas->isEmpty())
            <x-site.empty-state title="Rincian kurikulum segera tersedia" icon="academic-cap" />
        @else
            <x-site.skema-list :skemas="$skemas" detailed />
            <div class="mt-10 rounded-site bg-brand-section-bg p-6">
                <h2 class="font-display text-xl text-brand-primary">Penilaian 4 Dimensi</h2>
                @php($w = App\Services\SkemaScore::weights())
                <p class="mt-2 text-gray-700">Setiap skema dinilai dari Knowledge {{ $w['knowledge'] }}%, Skill {{ $w['skill'] }}%, Character {{ $w['character'] }}%, dan Social Cohesion {{ $w['social'] }}%. Sertifikat terbit untuk siswa yang lulus dan dapat diverifikasi keasliannya secara online.</p>
            </div>
        @endif
    </div>
</x-site.layout>
