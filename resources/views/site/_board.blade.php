{{-- Daftar pengurus satu periode, urut divisi -> jabatan -> anggota (PRD 8.4). Hanya nama & jabatan (PRD 9). --}}
@if ($periods->count() > 1)
    <form method="GET" class="mb-8 flex flex-wrap items-center gap-3">
        <label for="periode" class="text-sm font-semibold">Periode</label>
        <select id="periode" name="periode" onchange="this.form.submit()" class="rounded-md border-brand-border text-sm">
            @foreach ($periods as $p)
                <option value="{{ $p->id }}" @selected($period && $period->id === $p->id)>{{ $p->name }}{{ $p->is_active ? ' (aktif)' : '' }}</option>
            @endforeach
        </select>
        <noscript><button class="text-sm underline">Tampilkan</button></noscript>
    </form>
@endif
@if ($period && $period->boardMembers->isNotEmpty())
    @foreach ($period->boardMembers->groupBy(fn ($b) => $b->division?->name ?? 'Pengurus Inti') as $group => $rows)
        <section class="mb-10">
            <h2 class="mb-4 text-xl">{{ $group }}</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($rows as $board)
                    <x-site.card-person :name="$board->user->name" :position="$board->position->name"
                        :photo="$board->user->photo && Storage::disk('public')->exists('user/' . $board->user->photo) ? Storage::disk('public')->url('user/' . $board->user->photo) : null" />
                @endforeach
            </div>
        </section>
    @endforeach
@else
    <x-site.empty-state title="Data kepengurusan belum tersedia" icon="users" />
@endif
