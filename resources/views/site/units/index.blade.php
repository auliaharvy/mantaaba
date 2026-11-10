<x-site.layout title="Ekosistem" description="Unit bisnis dan laboratorium kehidupan Rumah Mantaaba Sigaling: asrama, kebun, kolam, dapur, dan usaha afiliasi.">
    <x-site.breadcrumb-header title="Ekosistem & Unit Bisnis" :crumbs="['Ekosistem']" subtitle="Tempat siswa mempraktikkan manajemen, akuntansi, dan komunikasi dalam operasional nyata." />
    <div class="container-site py-12">
        @php($groups = $units->groupBy('type'))
        @forelse (App\Models\BusinessUnit::TYPES as $type => $label)
            @continue(! $groups->has($type))
            <section class="mb-12 last:mb-0">
                <x-site.section-heading :title="$label" />
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($groups[$type] as $unit)
                        <x-site.pillar-card :title="$unit->name" :meta="$unit->field" :href="route('site.units.show', $unit->slug)"
                            :image="$unit->cover ? Storage::disk('public')->url($unit->cover) : null" icon="building">{{ $unit->summary }}</x-site.pillar-card>
                    @endforeach
                </div>
            </section>
        @empty
        @endforelse
        @if ($units->isEmpty())
            <x-site.empty-state title="Profil unit bisnis segera hadir" icon="building" />
        @endif
    </div>
</x-site.layout>
