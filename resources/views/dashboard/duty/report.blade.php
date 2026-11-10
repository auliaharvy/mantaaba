<x-dashboard.page title="Laporan Piket" :crumbs="['Asrama', 'Jadwal Piket', 'Laporan']">
    <div class="card">
        <div class="card-header">
            <form method="GET" class="card-title d-flex gap-3 align-items-center"><label for="bulan" class="fs-6 fw-bold">Bulan</label>
                <input id="bulan" type="month" name="bulan" value="{{ $month->format('Y-m') }}" class="form-control form-control-sm w-auto" onchange="this.form.submit()"></form>
            <div class="card-toolbar"><a href="{{ route('duty.index') }}" class="btn btn-sm btn-light"><i class="fa fa-arrow-left"></i></a></div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-bordered gs-3 gy-3">
                    <thead><tr class="fw-bold bg-light fs-7"><th>Anggota</th><th>Jadwal</th><th>Sudah lewat</th><th>Selesai</th><th>Terlewat</th><th>Persentase</th></tr></thead>
                    <tbody>
                        @forelse ($rows as $r)
                            <tr><td><a href="{{ route('member.show', [$r['member'], 'tab' => 'piket']) }}">{{ $r['member']->name }}</a></td><td>{{ $r['total'] }}</td><td>{{ $r['due'] }}</td><td>{{ $r['done'] }}</td><td>{{ $r['missed'] }}</td>
                                <td><span class="fw-bolder {{ $r['percent'] !== null && $r['percent'] < 80 ? 'text-danger' : 'text-success' }}">{{ $r['percent'] !== null ? $r['percent'] . '%' : '-' }}</span></td></tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">Tidak ada jadwal di bulan ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-muted fs-8 mb-0">Persentase = (selesai + diverifikasi) ÷ jadwal yang sudah lewat waktunya. Dipakai di ringkasan penilaian.</p>
        </div>
    </div>
</x-dashboard.page>
