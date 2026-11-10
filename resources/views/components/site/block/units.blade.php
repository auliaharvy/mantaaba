@props(['data', 'alt' => false, 'first' => false])
@php($units = App\Models\BusinessUnit::public()->when(! empty($data['unit_ids']), fn ($q) => $q->whereIn('id', $data['unit_ids']))->get())
<section class="py-14 {{ $alt ? 'bg-brand-section-bg' : '' }}">
    <div class="container-site">
        <x-site.section-heading :title="$data['title'] ?: 'Ekosistem & Unit Bisnis'" :description="$data['intro'] ?? null" center />
        @if ($units->isEmpty())
            <x-site.empty-state title="Profil unit bisnis segera hadir" icon="building" />
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($units as $unit)
                    <x-site.pillar-card :title="$unit->name" :meta="$unit->field ?: App\Models\BusinessUnit::TYPES[$unit->type]" :href="route('site.units.show', $unit->slug)"
                        :image="$unit->cover ? Storage::disk('public')->url($unit->cover) : null" icon="building">{{ $unit->summary }}</x-site.pillar-card>
                @endforeach
            </div>
        @endif
    </div>
</section>
