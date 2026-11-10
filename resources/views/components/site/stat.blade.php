@props(['value', 'label', 'icon' => null])
<div {{ $attributes->merge(['class' => 'rounded-site border border-brand-border bg-white p-5 text-center shadow-sm']) }}>
    @if ($icon)<x-site.icon :name="$icon" class="mx-auto mb-2 h-8 w-8 text-brand-gold" />@endif
    <p class="font-display text-3xl font-bold text-brand-primary">{{ is_numeric($value) ? number_format($value, 0, ',', '.') : $value }}</p>
    <p class="mt-1 text-sm text-gray-600">{{ $label }}</p>
</div>
