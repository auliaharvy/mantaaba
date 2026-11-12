{{-- Daftar skema publik (halaman Kurikulum & blok `skemas`). --}}
@props(['skemas', 'detailed' => false])
<div class="grid gap-6 {{ $detailed ? '' : 'sm:grid-cols-2 lg:grid-cols-3' }}">
    @foreach ($skemas as $skema)
        <x-site.pillar-card :code="$skema->code" :meta="$skema->month_label" :title="$skema->name" :href="$detailed ? null : route('site.curriculum') . '#' . Str::lower($skema->code)" :id="Str::lower($skema->code)" class="min-w-0">
            <p>{{ $skema->description }}</p>
            @if ($detailed)
                <div x-data="{ all: false }" x-init="if (location.hash.startsWith('#kompetensi-')) all = true">
                {{-- Skema → unit kompetensi (tabel kompetensi, maks. 10 lalu "lihat selengkapnya") → elemen (modul, bisa disembunyikan) → KUK --}}
                @if ($skema->units->isNotEmpty())
                    <div class="mt-4">
                        <a href="{{ route('site.curriculum.pdf', $skema->code) }}" class="inline-flex items-center gap-2 rounded-md border border-brand-border px-3 py-1.5 text-sm font-semibold text-brand-primary hover:bg-brand-section-bg">
                            <x-site.icon name="download" class="h-4 w-4" /> Unduh Pemetaan Standar Kompetensi (PDF)</a>
                    </div>
                @endif
                @if ($skema->units->isNotEmpty() || $skema->character_matrix)
                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full min-w-[720px] text-left text-sm">
                            <thead><tr class="bg-brand-section-bg"><th class="p-2">Kompetensi</th><th class="p-2">Fokus teknis</th><th class="p-2">'Asabiyah</th><th class="p-2">Fastabiqul Khairat</th></tr></thead>
                            <tbody>
                                @if ($skema->units->isNotEmpty())
                                    @foreach ($skema->units as $unit)
                                        <tr class="scroll-mt-28 border-b border-brand-border align-top" id="kompetensi-{{ Str::lower($unit->code) }}"
                                            @if ($loop->index >= 10) x-show="all" x-cloak @endif>
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
                        @if ($skema->units->count() > 10)
                            <button type="button" class="mt-3 text-sm font-semibold text-brand-accent hover:underline" @click="all = ! all"
                                x-text="all ? 'Tampilkan lebih sedikit' : 'Lihat selengkapnya ({{ $skema->units->count() }} kompetensi)'">Lihat selengkapnya ({{ $skema->units->count() }} kompetensi)</button>
                        @endif
                    </div>
                @endif
                @if ($skema->modules->isNotEmpty())
                    <details class="group mt-6 rounded-site border border-brand-border">
                        <summary class="flex cursor-pointer items-center justify-between gap-3 px-4 py-3 font-semibold text-brand-heading">
                            <span>Modul ({{ $skema->modules->count() }} elemen unit kompetensi)</span>
                            <span class="text-sm font-normal text-brand-accent"><span class="group-open:hidden">Tampilkan</span><span class="hidden group-open:inline">Sembunyikan</span></span>
                        </summary>
                        <ol class="list-decimal space-y-1 px-4 pb-4 pl-9 {{ $skema->modules->count() > 8 ? 'lg:columns-2 lg:gap-10' : '' }}">
                            @foreach ($skema->modules as $module)
                                @php($unit = $skema->units->firstWhere('id', $module->competency_unit_id))
                                <li class="break-inside-avoid">{{ $module->title }}
                                    @if ($unit)<a href="#kompetensi-{{ Str::lower($unit->code) }}" @click.prevent="all = true; history.replaceState(null, '', '#kompetensi-{{ Str::lower($unit->code) }}'); $nextTick(() => document.getElementById('kompetensi-{{ Str::lower($unit->code) }}').scrollIntoView({ behavior: 'smooth' }))" class="ml-1 whitespace-nowrap rounded bg-brand-section-bg px-1.5 py-0.5 text-xs font-semibold text-brand-accent hover:underline" title="{{ $unit->title }}">{{ $unit->code }}</a>@endif
                                </li>
                            @endforeach
                        </ol>
                    </details>
                @endif
                </div>
            @endif
        </x-site.pillar-card>
    @endforeach
</div>
