<x-site.layout :title="$unit->name" :description="$unit->summary" :image="$unit->cover ? Storage::disk('public')->url($unit->cover) : null">
    <x-site.breadcrumb-header :title="$unit->name" :crumbs="['Ekosistem' => route('site.units'), $unit->name]" :subtitle="$unit->field" />
    <div class="container-site grid gap-10 py-12 lg:grid-cols-3">
        <article class="lg:col-span-2">
            @if ($unit->cover)<img src="{{ Storage::disk('public')->url($unit->cover) }}" alt="{{ $unit->name }}" class="mb-8 w-full rounded-site object-cover">@endif
            @if ($unit->summary)<p class="mb-6 text-lg text-gray-700">{{ $unit->summary }}</p>@endif
            <div class="prose-cms">{!! $unit->body !!}</div>
            @if ($unit->gallery && $unit->gallery->photos->isNotEmpty())
                <h2 class="mb-4 mt-10 font-display text-xl text-brand-primary">Dokumentasi</h2>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                    @foreach ($unit->gallery->photos->take(9) as $photo)
                        <img src="{{ Storage::disk('public')->url($photo->image) }}" alt="{{ $photo->caption ?? $unit->name }}" class="aspect-[4/3] w-full rounded-site object-cover" loading="lazy">
                    @endforeach
                </div>
                <a href="{{ route('site.gallery.show', $unit->gallery->slug) }}" class="mt-4 inline-flex items-center gap-1 font-semibold text-brand-accent">Album lengkap <x-site.icon name="arrow-right" class="h-4 w-4" /></a>
            @endif
        </article>
        <aside class="space-y-4 self-start rounded-site border border-brand-border bg-brand-section-bg p-6">
            @if ($unit->logo)<img src="{{ Storage::disk('public')->url($unit->logo) }}" alt="Logo {{ $unit->name }}" class="h-20 w-auto">@endif
            <p class="text-xs font-bold uppercase tracking-widest text-brand-accent">{{ App\Models\BusinessUnit::TYPES[$unit->type] }}</p>
            @if ($unit->contact_name)<p class="flex gap-2"><x-site.icon name="users" class="h-5 w-5 flex-none text-brand-primary" />{{ $unit->contact_name }}</p>@endif
            @if ($unit->address)<p class="flex gap-2"><x-site.icon name="map-pin" class="h-5 w-5 flex-none text-brand-primary" />{{ $unit->address }}</p>@endif
            @if ($wa = App\Models\Setting::waLink($unit->phone))<p class="flex gap-2"><x-site.icon name="chat" class="h-5 w-5 flex-none text-brand-primary" /><a href="{{ $wa }}" target="_blank" rel="noopener" class="text-brand-accent underline">{{ $unit->phone }}</a></p>@endif
            @if ($unit->website)<p class="flex gap-2"><x-site.icon name="external" class="h-5 w-5 flex-none text-brand-primary" /><a href="{{ $unit->website }}" target="_blank" rel="noopener" class="break-all text-brand-accent underline">{{ preg_replace('#^https?://#', '', $unit->website) }}</a></p>@endif
        </aside>
    </div>
</x-site.layout>
