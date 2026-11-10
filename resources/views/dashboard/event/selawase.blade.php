<x-dashboard.page title="Laporan Sedulur Selawase" :crumbs="['Kegiatan', 'Agenda', 'Sedulur Selawase']">
    <div class="card">
        <div class="card-header">
            <div class="card-title flex-column align-items-start"><span class="fs-4 fw-bolder">Belum hadir sejak {{ $since->translatedFormat('j F Y') }}</span>
                <span class="text-muted fs-7">Alumni & anggota Fase II (magang, bekerja, ownership) tanpa kehadiran di agenda Sedulur Selawase 3 bulan terakhir.</span></div>
            <div class="card-toolbar gap-2">
                <a href="{{ route('event.selawase', ['export' => 1]) }}" class="btn btn-sm btn-light-primary"><i class="fa fa-file-csv"></i> Export CSV</a>
                <a href="{{ route('event.index') }}" class="btn btn-sm btn-light"><i class="fa fa-arrow-left"></i></a>
            </div>
        </div>
        <div class="card-body">
            @forelse ($members as $m)
                <div class="d-flex flex-wrap align-items-center gap-3 py-3 border-bottom">
                    <div class="flex-grow-1" style="min-width: 0"><a href="{{ route('member.show', $m) }}" class="fw-bold">{{ $m->name }}</a>
                        <div class="text-muted fs-7">{{ $m->member_number }} · {{ App\Models\Member::CATEGORIES[$m->category] }}{{ $m->phase ? ' · ' . App\Models\Member::PHASES[$m->phase] : '' }}{{ $m->batch ? ' · ' . $m->batch->code : '' }}</div></div>
                    <span class="fs-7 text-muted">Terakhir hadir: {{ $m->last_selawase ? \Illuminate\Support\Carbon::parse($m->last_selawase)->translatedFormat('j M Y') : 'belum pernah' }}</span>
                    @if ($wa = App\Models\Setting::waLink($m->phone, "Assalamu'alaikum {$m->name}, kami rindu kehadiran Anda di majelis Sedulur Selawase Rumah Mantaaba. Semoga bisa hadir di pertemuan berikutnya."))
                        <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-sm btn-light-success"><i class="fab fa-whatsapp"></i> Sapa</a>
                    @endif
                </div>
            @empty
                <p class="text-muted mb-0">Semua alumni & anggota Fase II hadir dalam 3 bulan terakhir. Alhamdulillah.</p>
            @endforelse
        </div>
    </div>
</x-dashboard.page>
