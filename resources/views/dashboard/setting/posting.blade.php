<x-dashboard.page title="Setting Akuntansi" :crumbs="['Keuangan', 'Setting Akuntansi']">
    <form method="POST" action="{{ route('posting-rule.update') }}" class="card mw-900px">
        @csrf @method('PUT')
        <div class="card-header"><div class="card-title flex-column align-items-start"><span class="fs-5 fw-bolder">Akun jurnal otomatis</span>
            <span class="text-muted fs-8">Dipakai voucher sistem dari pembelian stok, penjualan panen, rekap persediaan, kesehatan, dan tutup buku.</span></div></div>
        <div class="card-body">
            @foreach ($rules as $key => $rule)
                <div class="row align-items-center mb-3">
                    <label class="col-md-5 fs-7 fw-bold" for="rule-{{ $loop->index }}">{{ $rule['label'] }}</label>
                    <div class="col-md-7">
                        <select id="rule-{{ $loop->index }}" name="rules[{{ str_replace('.', '__', $key) }}]" class="form-select form-select-sm" data-control="select2">
                            @foreach ($accounts as $a)<option value="{{ $a->code }}" @selected($a->code === $rule['code'])>{{ $a->label() }}{{ $a->code === $rule['default'] ? ' (bawaan)' : '' }}</option>@endforeach
                        </select>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="card-footer text-end"><button class="btn btn-sm btn-primary">Simpan</button></div>
    </form>
</x-dashboard.page>
