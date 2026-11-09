<x-dashboard.page title="Import Anggota" :crumbs="['Organisasi', 'Data Anggota', 'Import CSV']">
    @if (session('import_failure_count'))
        <div class="card mb-5 border border-danger">
            <div class="card-header">
                <div class="card-title fs-4 fw-bolder text-danger">{{ session('import_failure_count') }} baris gagal diimpor</div>
                <div class="card-toolbar">
                    <a class="btn btn-sm btn-light-danger" href="{{ route('membership.import.failures', session('import_report')) }}"><i class="fa fa-download"></i> Unduh baris gagal</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-row-bordered gy-2">
                        <thead><tr class="fw-bold text-muted"><th>Baris</th><th>Nama</th><th>No. Anggota</th><th>Alasan</th></tr></thead>
                        <tbody>
                            @foreach (session('import_failures') as $failure)
                                <tr><td>{{ $failure['line'] }}</td><td>{{ $failure['row']['nama'] ?? '' }}</td><td>{{ $failure['row']['nomor_anggota'] ?? '' }}</td><td>{{ $failure['reason'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if (session('import_failure_count') > 100)<div class="text-muted">Menampilkan 100 baris pertama; unduh file untuk semuanya.</div>@endif
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('membership.import.store') }}" enctype="multipart/form-data" class="card">
        @csrf
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Import dari CSV</div>
            <div class="card-toolbar gap-2">
                <a class="btn btn-sm btn-light" href="{{ route('membership.import.template') }}"><i class="fa fa-file-csv"></i> Unduh template</a>
                <a class="btn btn-sm btn-light-primary" href="{{ route('membership.index') }}"><i class="fa fa-arrow-left"></i> Kembali</a>
            </div>
        </div>
        <div class="card-body">
            <ul class="text-gray-700 mb-6">
                <li>Kolom: <code>{{ implode(',', App\Http\Controllers\MembershipController::EXPORT_COLUMNS) }}</code></li>
                <li><code>jenis</code>: utama / madya / pratama / kehormatan. <code>kode_pc</code>: kode PC di menu Pengurus Cabang. <code>brevet</code>: mis. <code>A,B</code>. Tanggal: <code>2020-01-15</code> atau <code>15/01/2020</code>.</li>
                <li>Baris valid langsung disimpan; baris gagal ditampilkan beserta alasannya. Email yang sudah dipakai akun Umum akan dinaikkan menjadi Anggota.</li>
            </ul>
            <x-dashboard.field name="file" label="File CSV (maks 5 MB)" type="file" accept=".csv,text/csv" required />
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="send_later" name="send_later" value="1">
                <label class="form-check-label" for="send_later">Kirim email aktivasi nanti (bisa dikirim per anggota dari halaman rinciannya)</label>
            </div>
        </div>
        <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Import</button></div>
    </form>
</x-dashboard.page>
