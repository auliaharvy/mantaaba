@props(['data', 'alt' => false, 'first' => false])
{{-- AC 9.4: hanya anggota yang mengizinkan (show_on_site). Tanpa data pribadi. --}}
@php
    $category = $data['category'] ?? 'semua';
    $members = App\Models\Member::with('batch:id,code')->where('show_on_site', true)->whereIn('status', ['aktif', 'lulus'])
        ->whereIn('category', $category === 'semua' ? ['siswa', 'alumni'] : [$category])
        ->orderBy('name')->take((int) ($data['limit'] ?? 8))->get(['id', 'name', 'category', 'batch_id', 'photo']);
@endphp
@if ($members->isNotEmpty())
    <section class="py-14 {{ $alt ? 'bg-brand-section-bg' : '' }}">
        <div class="container-site">
            <x-site.section-heading :title="$data['title'] ?: 'Siswa & Alumni'" center />
            <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($members as $member)
                    <figure class="text-center">
                        @if ($member->photoUrl())
                            <img src="{{ $member->photoUrl() }}" alt="Foto {{ $member->name }}" class="mx-auto h-28 w-28 rounded-full border-4 border-brand-gold/40 object-cover" loading="lazy">
                        @else
                            <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-full bg-brand-section-bg font-display text-2xl text-brand-primary" aria-hidden="true">{{ mb_substr($member->name, 0, 1) }}</div>
                        @endif
                        <figcaption class="mt-3"><span class="block font-bold text-brand-heading">{{ $member->name }}</span>
                            <span class="text-sm text-brand-accent">{{ App\Models\Member::CATEGORIES[$member->category] }}{{ $member->batch ? ' · ' . $member->batch->code : '' }}</span></figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>
@endif
