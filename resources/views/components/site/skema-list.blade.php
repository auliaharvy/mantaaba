{{-- Daftar skema publik (halaman Kurikulum & blok `skemas`). --}}
@props(['skemas', 'detailed' => false])
<div class="grid gap-6 {{ $detailed ? '' : 'sm:grid-cols-2 lg:grid-cols-3' }}">
    @foreach ($skemas as $skema)
        <x-site.pillar-card :code="$skema->code" :meta="$skema->month_label" :title="$skema->name" :href="$detailed ? null : route('site.curriculum') . '#' . Str::lower($skema->code)" :id="Str::lower($skema->code)">
            <p>{{ $skema->description }}</p>
            @if ($detailed)
                @if ($skema->modules->isNotEmpty())
                    <p class="mt-4 font-semibold text-brand-heading">Modul</p>
                    <ul class="mt-1 list-disc pl-5">@foreach ($skema->modules as $module)<li>{{ $module->title }}</li>@endforeach</ul>
                @endif
                @if ($skema->character_matrix)
                    <div class="mt-5 overflow-x-auto">
                        <table class="w-full min-w-[640px] text-left text-sm">
                            <thead><tr class="bg-brand-section-bg"><th class="p-2">Kompetensi</th><th class="p-2">Fokus teknis</th><th class="p-2">'Asabiyah</th><th class="p-2">Fastabiqul Khairat</th></tr></thead>
                            <tbody>
                                @foreach ($skema->character_matrix as $row)
                                    <tr class="border-b border-brand-border align-top"><td class="p-2 font-semibold">{{ $row['competency'] }}</td><td class="p-2">{{ $row['technical'] ?? '' }}</td><td class="p-2">{{ $row['asabiyah'] ?? '' }}</td><td class="p-2">{{ $row['fastabiqul'] ?? '' }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </x-site.pillar-card>
    @endforeach
</div>
