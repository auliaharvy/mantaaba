@php($editing = $account->exists)
<x-dashboard.page :title="$editing ? 'Ubah Akun ' . $account->code : 'Tambah Akun'" :crumbs="['Keuangan', 'COA', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('coa.update', $account) : route('coa.store') }}" class="card mw-900px">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="card-body row">
            @if ($editing && $account->lines_count)<div class="col-12"><div class="alert alert-light-warning fs-7">Akun sudah dipakai {{ $account->lines_count }} baris jurnal: kode, tipe, dan header tidak bisa diubah.</div></div>@endif
            <div class="col-md-3"><x-dashboard.field name="code" label="Kode" :value="$account->code" required placeholder="5-1700" /></div>
            <div class="col-md-5"><x-dashboard.field name="name" label="Nama" :value="$account->name" required /></div>
            <div class="col-md-4"><x-dashboard.field name="type" label="Tipe" type="select" :options="App\Models\CoaAccount::TYPES" :value="$account->type" required /></div>
            <div class="col-md-6"><x-dashboard.field name="parent_id" label="Induk (akun header)" type="select" :options="$parents" :value="$account->parent_id" placeholder="— Akun utama —" /></div>
            <div class="col-md-6"><x-dashboard.field name="statement_group" label="Pos laporan" :value="$account->statement_group" placeholder="Beban program" /></div>
            <div class="col-md-4"><x-dashboard.field name="restriction" label="Pembatasan (aset neto/pendapatan)" type="select" :options="App\Models\CoaAccount::RESTRICTIONS" :value="$account->restriction" placeholder="—" /></div>
            <div class="col-md-4"><x-dashboard.field name="cash_flow_group" label="Kelompok arus kas" type="select" :options="App\Models\CoaAccount::CASH_FLOW_GROUPS" :value="$account->cash_flow_group" placeholder="—" /></div>
            <div class="col-md-4"><x-dashboard.field name="sort_order" label="Urutan" type="number" min="0" :value="$account->sort_order" /></div>
            <div class="col-12 d-flex flex-wrap gap-6 mb-4">
                @foreach (['is_header' => 'Akun header (tidak bisa diposting)', 'is_cash' => 'Akun kas', 'is_bank' => 'Akun bank', 'is_receivable' => 'Piutang (wajib pihak)', 'is_payable' => 'Utang (wajib pihak)', 'is_contra' => 'Akun kontra', 'is_active' => 'Aktif'] as $flag => $label)
                    <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="{{ $flag }}" value="1" @checked(old($flag, $account->{$flag}))> <span class="form-check-label">{{ $label }}</span></label>
                @endforeach
            </div>
            <div class="col-12 form-text">Saldo normal dihitung otomatis: aset & beban di debit; liabilitas, aset neto & pendapatan di kredit; akun kontra kebalikannya.</div>
        </div>
        <div class="card-footer text-end"><a href="{{ route('coa.index') }}" class="btn btn-sm btn-light">Batal</a> <button class="btn btn-sm btn-primary">Simpan</button></div>
    </form>
</x-dashboard.page>
