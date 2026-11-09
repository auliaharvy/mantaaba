@props(['href' => null, 'variant' => 'primary', 'size' => 'md'])
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-md font-bold uppercase tracking-wide transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary focus-visible:ring-offset-2';
    $sizes = ['sm' => 'px-4 py-2 text-xs', 'md' => 'px-6 py-3 text-sm'];
    $variants = [
        'primary' => 'bg-brand-secondary text-white hover:bg-brand-primary-hover',
        'outline' => 'border-2 border-brand-secondary text-brand-secondary hover:bg-brand-secondary hover:text-white',
        'light' => 'bg-white text-brand-heading hover:bg-brand-section-bg',
        'ghost-light' => 'border-2 border-white/80 text-white hover:bg-white hover:text-brand-heading',
    ];
    $classes = "{$base} {$sizes[$size]} {$variants[$variant]}";
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
