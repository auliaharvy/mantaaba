{{-- Linimasa vertikal (roadmap Fase I–II). items: [['time', 'title', 'text']] --}}
@props(['items'])
<ol {{ $attributes->merge(['class' => 'relative mx-auto max-w-3xl border-l-2 border-brand-gold/60 pl-8']) }}>
    @foreach ($items as $item)
        <li class="relative mb-8 last:mb-0">
            <span class="absolute -left-[2.6rem] top-1 flex h-5 w-5 items-center justify-center rounded-full border-2 border-brand-gold bg-white" aria-hidden="true"><span class="h-2 w-2 rounded-full bg-brand-accent"></span></span>
            @if (! empty($item['time']))<p class="text-xs font-bold uppercase tracking-widest text-brand-accent">{{ $item['time'] }}</p>@endif
            <h3 class="mt-1 text-lg text-brand-primary">{{ $item['title'] }}</h3>
            @if (! empty($item['text']))<p class="mt-1 text-gray-700">{{ $item['text'] }}</p>@endif
        </li>
    @endforeach
</ol>
