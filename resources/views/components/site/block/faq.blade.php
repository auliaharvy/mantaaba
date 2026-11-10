@props(['data', 'alt' => false, 'first' => false])
<section class="py-14 {{ $alt ? 'bg-brand-section-bg' : '' }}">
    <div class="container-site max-w-3xl">
        <x-site.section-heading :title="$data['title'] ?: 'Pertanyaan yang Sering Diajukan'" center />
        <div class="divide-y divide-brand-border rounded-site border border-brand-border bg-white">
            @foreach ($data['items'] ?? [] as $item)
                <details class="group p-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-brand-heading">
                        {{ $item['question'] }} <x-site.icon name="chevron-down" class="h-5 w-5 flex-none transition group-open:rotate-180" />
                    </summary>
                    <p class="mt-3 whitespace-pre-line text-gray-700">{{ $item['answer'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
