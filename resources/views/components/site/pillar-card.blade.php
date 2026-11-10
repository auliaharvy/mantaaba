{{-- Kartu pilar / skema: kode besar, judul, teks (5 skema, 2 pilar filosofis). --}}
@props(['code' => null, 'title', 'meta' => null, 'icon' => null, 'image' => null, 'href' => null])
<div {{ $attributes->merge(['class' => 'relative flex flex-col rounded-site border border-brand-border border-t-4 border-t-brand-gold bg-white p-6 shadow-sm transition hover:shadow-md']) }}>
    @if ($image)
        <img src="{{ $image }}" alt="" class="-mx-6 -mt-6 mb-5 aspect-[16/10] w-[calc(100%+3rem)] max-w-none rounded-t-site object-cover" loading="lazy">
    @elseif ($code)
        <p class="font-display text-3xl font-bold text-brand-gold">{{ $code }}</p>
    @elseif ($icon)
        <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand-section-bg"><x-site.icon :name="$icon" class="h-6 w-6 text-brand-primary" /></span>
    @endif
    @if ($meta)<p class="text-xs font-bold uppercase tracking-widest text-brand-accent">{{ $meta }}</p>@endif
    <h3 class="mt-1 text-lg text-brand-primary">@if ($href)<a href="{{ $href }}" class="after:absolute after:inset-0 hover:text-brand-accent">{{ $title }}</a>@else{{ $title }}@endif</h3>
    @if (trim($slot))<div class="mt-2 flex-1 text-sm text-gray-700">{{ $slot }}</div>@endif
</div>
