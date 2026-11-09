@props(['stats'])
@php
    $items = [
        ['value' => 6, 'label' => 'Bulan Inkubasi', 'icon' => 'shield'],
        ['value' => 5, 'label' => 'Skema Kompetensi', 'icon' => 'document'],
        ['value' => $stats['events'], 'label' => 'Agenda Terselenggara', 'icon' => 'calendar'],
        ['value' => $stats['news'], 'label' => 'Berita & Kabar', 'icon' => 'users'],
    ];
@endphp
<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach ($items as $item)
        <div class="rounded-lg border border-brand-border bg-white p-5 text-center shadow-sm">
            <x-site.icon :name="$item['icon']" class="mx-auto mb-2 h-8 w-8 text-brand-primary" />
            <p class="text-3xl font-bold text-brand-heading">{{ number_format($item['value'], 0, ',', '.') }}</p>
            <p class="mt-1 text-sm text-gray-600">{{ $item['label'] }}</p>
        </div>
    @endforeach
</div>
