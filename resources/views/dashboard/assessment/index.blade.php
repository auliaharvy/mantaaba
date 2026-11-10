{{-- Penilaian 4 dimensi massal (PRD 9.7). Nilai akhir dihitung ulang di server; pratinjau di sini hanya bantuan. --}}
@php($canUpdate = auth()->user()->can('assessment-update'))
<x-dashboard.page title="Penilaian" :crumbs="['Pembelajaran', 'Penilaian']">
    <div class="card mb-5">
        <div class="card-body d-flex flex-wrap gap-3 align-items-end">
            <form method="GET" class="d-flex flex-wrap gap-3">
                <div><label class="form-label fs-7" for="f-batch">Angkatan</label>
                    <select id="f-batch" name="batch" class="form-select form-select-sm" onchange="this.form.submit()">@foreach ($batches as $b)<option value="{{ $b->id }}" @selected($b->id === $batch?->id)>{{ $b->code }}</option>@endforeach</select></div>
                <div><label class="form-label fs-7" for="f-skema">Skema</label>
                    <select id="f-skema" name="skema" class="form-select form-select-sm" onchange="this.form.submit()">@foreach ($skemas as $s)<option value="{{ $s->id }}" @selected($s->id === $skema?->id)>{{ $s->code }} · {{ $s->name }}</option>@endforeach</select></div>
            </form>
            <div class="ms-auto text-muted fs-7">Bobot K{{ $weights['knowledge'] }} S{{ $weights['skill'] }} C{{ $weights['character'] }} SC{{ $weights['social'] }} · Lulus ≥ {{ $skema?->passScore() }}, C ≥ {{ $setting->min_character_score }}</div>
        </div>
    </div>

    @if (! $batch || ! $skema)
        <div class="card"><div class="card-body text-muted">Belum ada angkatan berjalan atau skema.</div></div>
    @elseif ($rows->isEmpty())
        <div class="card"><div class="card-body text-muted">Belum ada siswa angkatan ini yang terdaftar di skema {{ $skema->code }}. Ubah status angkatan menjadi Berjalan atau buka skema dari halaman angkatan.</div></div>
    @else
        <form method="POST" action="{{ route('assessment.store') }}" class="card" id="assessment-form" data-weights='@json($weights)' data-pass="{{ $skema->passScore() }}" data-min-character="{{ $setting->min_character_score }}">
            @csrf
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gs-5 gy-3 mb-0">
                        <thead><tr class="fw-bold bg-light fs-7">
                            <th>Siswa</th><th title="Rata-rata nilai tugas">Tugas</th><th title="Catatan pembinaan sejak skema dibuka: positif / pelanggaran">Pembinaan +/!</th>
                            <th title="Kehadiran agenda Kelas skema ini (hadir/total)">Hadir kelas</th><th title="Piket selesai sejak skema dibuka">Piket</th>
                            <th>K</th><th>S</th><th>C</th><th>SC</th><th>Akhir</th><th>Hasil</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php($e = $row['enrollment'])
                                @php($r = $e->result)
                                @php($locked = (bool) $r?->locked_at)
                                <tr data-row>
                                    <td class="text-nowrap"><a href="{{ route('member.show', $e->member) }}" class="fw-bold">{{ $e->member->name }}</a>
                                        <div class="fs-8 text-muted">{{ App\Models\SkemaEnrollment::STATUSES[$e->status] }}@if ($locked) · <i class="fa fa-lock"></i> {{ $r->activeCertificate?->number ?? 'terkunci' }}@endif</div></td>
                                    <td>{{ $row['assignment'] !== null ? number_format($row['assignment'], 0) : '-' }}</td>
                                    <td title="{{ $row['dimensions']->map(fn ($n, $d) => (App\Models\CoachingNote::DIMENSIONS[$d] ?? $d) . ': ' . $n)->implode(', ') }}">{{ $row['positive'] }} / {{ $row['violations'] }}</td>
                                    <td>{{ $row['classes'] ?? '—' }}</td><td>{{ $row['duty'] !== null ? $row['duty'] . '%' : '—' }}</td>
                                    @foreach (array_keys(App\Models\SkemaResult::DIMENSIONS) as $dim)
                                        <td><input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm w-75px" name="results[{{ $e->id }}][{{ $dim }}]" data-dim="{{ $dim }}"
                                            value="{{ old("results.{$e->id}.{$dim}", $r?->{$dim}) }}" @disabled($locked || ! $canUpdate || $e->isLocked()) aria-label="{{ App\Models\SkemaResult::DIMENSIONS[$dim] }} {{ $e->member->name }}"></td>
                                    @endforeach
                                    <td class="fw-bolder" data-final>{{ $r?->final_score !== null ? number_format($r->final_score, 2, ',', '.') : '-' }}</td>
                                    <td class="text-nowrap fs-7" data-result>{{ $r ? ($r->passed ? 'Lulus · ' . $r->predicate : 'Belum lulus') : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                <span class="text-muted fs-7 me-auto">Baris yang belum lengkap (4 nilai) tidak disimpan. Nilai bersertifikat terkunci; cabut sertifikat untuk mengubah.</span>
                @if ($canUpdate)<button class="btn btn-sm btn-primary">Simpan semua</button>@endif
            </div>
        </form>
        @can('certificate-issue')
            <form method="POST" action="{{ route('assessment.issue') }}" class="text-end mt-4" onsubmit="return confirm('Terbitkan sertifikat untuk semua siswa yang lulus dan belum bersertifikat?')">
                @csrf <input type="hidden" name="batch" value="{{ $batch->id }}"><input type="hidden" name="skema" value="{{ $skema->id }}">
                <button class="btn btn-sm btn-success"><i class="fa fa-certificate"></i> Terbitkan sertifikat yang lulus</button>
            </form>
        @endcan
        <x-slot name="javascript">
            <script>
                (function () {
                    const form = document.getElementById('assessment-form');
                    const w = JSON.parse(form.dataset.weights), pass = +form.dataset.pass, minC = +form.dataset.minCharacter;
                    const keys = { knowledge: 'knowledge', skill: 'skill', character: 'character', social_cohesion: 'social' };
                    form.querySelectorAll('tr[data-row]').forEach((tr) => tr.addEventListener('input', () => {
                        const v = {}; tr.querySelectorAll('[data-dim]').forEach((i) => v[i.dataset.dim] = i.value);
                        if (Object.values(v).some((x) => x === '')) return;
                        const final = Math.round(Object.entries(keys).reduce((s, [dim, k]) => s + (+v[dim]) * w[k], 0)) / 100;
                        const ok = final >= pass && +v.character >= minC;
                        tr.querySelector('[data-final]').textContent = final.toFixed(2).replace('.', ',');
                        tr.querySelector('[data-result]').textContent = ok ? 'Lulus (pratinjau)' : 'Belum lulus' + (+v.character < minC ? ' (C<' + minC + ')' : '');
                    }));
                })();
            </script>
        </x-slot>
    @endif
</x-dashboard.page>
