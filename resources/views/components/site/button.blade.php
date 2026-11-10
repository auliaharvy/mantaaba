@props(['href' => null, 'variant' => 'primary', 'size' => 'md'])
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-site font-bold uppercase tracking-wide transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-gold focus-visible:ring-offset-2';
    $sizes = ['sm' => 'px-4 py-2 text-xs', 'md' => 'px-6 py-3 text-sm'];
    $variants = [
        'primary' => 'bg-brand-primary text-white hover:bg-brand-primary-hover',
        'accent' => 'bg-brand-accent text-white hover:bg-brand-primary-hover',
        'gold' => 'bg-brand-gold text-brand-heading hover:bg-white',
        'outline' => 'border-2 border-brand-primary text-brand-primary hover:bg-brand-primary hover:text-white',
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
