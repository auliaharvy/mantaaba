@props(['data', 'alt' => false, 'first' => false])
<section class="py-14 {{ $alt ? 'bg-brand-section-bg' : '' }}">
    <div class="container-site">
        @if (! empty($data['title']))<x-site.section-heading :title="$data['title']" center />@endif
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($data['items'] ?? [] as $person)
                <figure class="rounded-site border border-brand-border bg-white p-6 text-center shadow-sm">
                    @if (! empty($person['photo']))
                        <img src="{{ Storage::disk('public')->url($person['photo']) }}" alt="Foto {{ $person['name'] }}" class="mx-auto h-28 w-28 rounded-full border-4 border-brand-gold/40 object-cover" loading="lazy">
                    @else
                        <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-full bg-brand-section-bg font-display text-2xl text-brand-primary" aria-hidden="true">{{ collect(explode(' ', $person['name']))->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}</div>
                    @endif
                    <figcaption class="mt-4">
                        <p class="font-bold text-brand-heading">{{ $person['name'] }}</p>
                        @if (! empty($person['role']))<p class="text-sm font-semibold text-brand-accent">{{ $person['role'] }}</p>@endif
                        @if (! empty($person['quote']))<p class="mt-3 text-sm italic text-gray-600">“{{ $person['quote'] }}”</p>@endif
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
