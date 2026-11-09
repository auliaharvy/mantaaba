{{-- Iuran anggota (PRD 8.3). Dipakai di detail anggota (admin) dan profil (anggota sendiri, $readonly). --}}
@php($readonly = $readonly ?? false)
<div class="card mb-5">
    <div class="card-header">
        <div class="card-title fs-4 fw-bolder">Iuran</div>
        <div class="card-toolbar">
            @php($duesStatus = $member->dues_status)
            <span class="badge badge-light-{{ ['lunas' => 'success', 'belum_lunas' => 'warning', 'menunggak' => 'danger'][$duesStatus] }} fs-7">
                {{ now()->year }}: {{ App\Models\Member::DUES_STATUSES[$duesStatus] }}
            </span>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-3">
                <thead><tr class="fw-bold text-muted"><th>Tahun</th><th>Nominal</th><th>Tanggal bayar</th><th>Bukti</th><th></th></tr></thead>
                <tbody>
                    @forelse ($member->dues as $due)
                        <tr>
                            <td>{{ $due->year }}</td>
                            <td>Rp{{ number_format($due->amount, 0, ',', '.') }}</td>
                            <td>{{ $due->paid_at->translatedFormat('j M Y') }}</td>
                            <td>
                                @if ($due->proof_file)
                                    @can('viewProof', $due)<a href="{{ route('dues.proof', $due) }}">Unduh</a>@endcan
                                @else - @endif
                            </td>
                            <td class="text-end">
                                @if (! $readonly)
                                    @can('delete', $due)
                                        <form method="POST" action="{{ route('dues.destroy', $due) }}" onsubmit="return confirm('Hapus catatan iuran {{ $due->year }}?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-icon btn-light-danger" title="Hapus"><i class="fa fa-trash"></i></button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">Belum ada catatan iuran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (! $readonly)
            @can('create', App\Models\MemberDue::class)
                <form method="POST" action="{{ route('membership.dues.store', $member) }}" enctype="multipart/form-data" class="row g-3 mt-2 border-top pt-5">
                    @csrf
                    <div class="col-12 fw-bolder">Catat pembayaran</div>
                    <div class="col-sm-3"><x-dashboard.field name="year" label="Tahun" type="number" :value="now()->year" required /></div>
                    <div class="col-sm-4"><x-dashboard.field name="amount" label="Nominal (Rp)" type="number" min="0" :value="$defaultDues" required /></div>
                    <div class="col-sm-5"><x-dashboard.field name="paid_at" label="Tanggal bayar" type="date" :value="now()" required /></div>
                    <div class="col-sm-6"><x-dashboard.field name="proof_file" label="Bukti transfer (gambar/PDF, maks 5 MB)" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" /></div>
                    <div class="col-sm-6"><x-dashboard.field name="notes" label="Catatan" /></div>
                    <div class="col-12 text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan iuran</button></div>
                </form>
            @endcan
        @endif
    </div>
</div>
