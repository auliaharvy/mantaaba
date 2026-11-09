@props(['stats'])
@php
    $items = [
        ['value' => $stats['members'], 'label' => 'Total Anggota', 'icon' => 'users'],
        ['value' => $stats['licensed'], 'label' => 'Anggota Berizin (Utama)', 'icon' => 'shield'],
        ['value' => $stats['branches'], 'label' => 'Pengurus Cabang', 'icon' => 'building'],
        ['value' => $stats['events'], 'label' => 'Event Terselenggara', 'icon' => 'calendar'],
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
