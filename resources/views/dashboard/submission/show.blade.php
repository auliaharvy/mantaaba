@php($p = $progress)
<x-dashboard.page title="Tugas Masuk" :crumbs="['Pembelajaran', 'Tugas Masuk', $p->user->name]">
    <div class="row g-5">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header"><div class="card-title flex-column align-items-start"><span class="fs-4 fw-bolder">{{ $p->lesson->title }}</span>
                    <span class="text-muted fs-7">{{ $p->lesson->module->skema->code }} › {{ $p->lesson->module->title }} · {{ $p->user->name }}{{ $p->user->member ? ' (' . $p->user->member->member_number . ')' : '' }}</span></div></div>
                <div class="card-body">
                    <div class="text-muted fs-7 mb-3">Dikirim {{ $p->submitted_at->translatedFormat('l, j F Y H:i') }}</div>
                    @if ($p->submission_text)<div class="border rounded p-4 bg-light mb-4" style="white-space: pre-line">{{ $p->submission_text }}</div>@endif
                    @if ($p->submission_file)<a href="{{ route('submission.file', $p) }}" class="btn btn-sm btn-light"><i class="fa fa-paperclip"></i> Unduh file tugas</a>@endif
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            @can('learning-review')
                <form method="POST" action="{{ route('submission.review', $p) }}" class="card mb-5">
                    @csrf @method('PATCH')
                    <div class="card-header"><div class="card-title fs-5 fw-bolder">{{ $p->reviewed_at ? 'Ubah nilai' : 'Beri nilai' }}</div></div>
                    <div class="card-body">
                        <x-dashboard.field name="score" label="Nilai (0–100)" type="number" min="0" max="100" :value="$p->score" required />
                        <x-dashboard.field name="feedback" label="Umpan balik" type="textarea" rows="5" :value="$p->feedback" required />
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <a href="{{ route('submission.index') }}" class="btn btn-sm btn-light">Kembali</a>
                        <button class="btn btn-sm btn-primary">Simpan nilai</button>
                    </div>
                </form>
                @if ($p->reviewed_at)
                    <form method="POST" action="{{ route('submission.reopen', $p) }}" class="text-end">@csrf<button class="btn btn-sm btn-light-warning" onclick="return confirm('Buka ulang tugas agar siswa bisa mengirim perbaikan?')"><i class="fa fa-redo"></i> Buka ulang tugas</button></form>
                @endif
            @else
                <div class="card"><div class="card-body">{{ $p->reviewed_at ? 'Nilai ' . $p->score . ' — ' . $p->feedback : 'Belum dinilai.' }}</div></div>
            @endcan
        </div>
    </div>
</x-dashboard.page>
