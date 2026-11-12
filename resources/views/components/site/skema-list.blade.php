{{-- Daftar skema publik (halaman Kurikulum & blok `skemas`). --}}
@props(['skemas', 'detailed' => false])
<div class="grid gap-6 {{ $detailed ? '' : 'sm:grid-cols-2 lg:grid-cols-3' }}">
    @foreach ($skemas as $skema)
        <x-site.pillar-card :code="$skema->code" :meta="$skema->month_label" :title="$skema->name" :href="$detailed ? null : route('site.curriculum') . '#' . Str::lower($skema->code)" :id="Str::lower($skema->code)" class="min-w-0">
            <p>{{ $skema->description }}</p>
            @if ($detailed)
                {{-- Skema → unit kompetensi (tabel kompetensi) → elemen (modul, menaut ke kompetensinya) → KUK --}}
                @if ($skema->modules->isNotEmpty())
                    <p class="mt-4 font-semibold text-brand-heading">Modul</p>
                    <ol class="mt-1 list-decimal space-y-1 pl-5 {{ $skema->modules->count() > 8 ? 'lg:columns-2 lg:gap-10' : '' }}">
                        @foreach ($skema->modules as $module)
                            @php($unit = $skema->units->firstWhere('id', $module->competency_unit_id))
                            <li class="break-inside-avoid">{{ $module->title }}
                                @if ($unit)<a href="#kompetensi-{{ Str::lower($unit->code) }}" class="ml-1 whitespace-nowrap rounded bg-brand-section-bg px-1.5 py-0.5 text-xs font-semibold text-brand-accent hover:underline" title="{{ $unit->title }}">{{ $unit->code }}</a>@endif
                            </li>
                        @endforeach
                    </ol>
                @endif
                @if ($skema->units->isNotEmpty() || $skema->character_matrix)
                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full min-w-[720px] text-left text-sm">
                            <thead><tr class="bg-brand-section-bg"><th class="p-2">Kompetensi</th><th class="p-2">Fokus teknis</th><th class="p-2">'Asabiyah</th><th class="p-2">Fastabiqul Khairat</th></tr></thead>
                            <tbody>
                                @if ($skema->units->isNotEmpty())
                                    @foreach ($skema->units as $unit)
                                        <tr class="scroll-mt-28 border-b border-brand-border align-top" id="kompetensi-{{ Str::lower($unit->code) }}">
                                            <td class="p-2"><span class="block text-xs font-semibold text-brand-accent">{{ $unit->code }}</span><span class="font-semibold">{{ $unit->title }}</span></td>
                                            <td class="p-2">{{ $unit->character['technical'] ?? '' }}</td><td class="p-2">{{ $unit->character['asabiyah'] ?? '' }}</td><td class="p-2">{{ $unit->character['fastabiqul'] ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    @foreach ($skema->character_matrix as $row)
                                        <tr class="border-b border-brand-border align-top"><td class="p-2 font-semibold">{{ $row['competency'] }}</td><td class="p-2">{{ $row['technical'] ?? '' }}</td><td class="p-2">{{ $row['asabiyah'] ?? '' }}</td><td class="p-2">{{ $row['fastabiqul'] ?? '' }}</td></tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </x-site.pillar-card>
    @endforeach
</div>
