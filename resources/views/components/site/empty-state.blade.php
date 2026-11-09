@props(['title' => 'Belum ada data', 'icon' => 'inbox'])
<div {{ $attributes->merge(['class' => 'rounded-lg border border-dashed border-brand-border bg-brand-section-bg px-6 py-12 text-center']) }}>
    <x-site.icon :name="$icon" class="mx-auto mb-3 h-10 w-10 text-brand-muted" />
    <p class="font-bold text-brand-heading">{{ $title }}</p>
    @if (trim($slot))<div class="mt-1 text-sm text-gray-600">{{ $slot }}</div>@endif
</div>
