@props(['data', 'alt' => false, 'first' => false])
<section class="py-14 {{ $alt ? 'bg-brand-section-bg' : '' }}">
    <div class="container-site">
        @if (! empty($data['title']))<x-site.section-heading :title="$data['title']" center />@endif
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($data['items'] ?? [] as $item)
                <figure class="flex flex-col rounded-site border border-brand-border bg-white p-6 shadow-sm">
                    <blockquote class="flex-1 text-gray-700"><span class="font-display text-4xl leading-none text-brand-gold" aria-hidden="true">“</span>{{ $item['quote'] }}</blockquote>
                    <figcaption class="mt-5 flex items-center gap-3">
                        @if (! empty($item['photo']))<img src="{{ Storage::disk('public')->url($item['photo']) }}" alt="" class="h-12 w-12 rounded-full object-cover" loading="lazy">@endif
                        <span><span class="block font-bold text-brand-heading">{{ $item['name'] }}</span>@if (! empty($item['role']))<span class="block text-sm text-brand-accent">{{ $item['role'] }}</span>@endif</span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
