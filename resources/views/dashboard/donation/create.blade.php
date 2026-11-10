<x-dashboard.page title="Catat Donasi" :crumbs="['Keuangan', 'Donasi', 'Catat']">
    <form method="POST" action="{{ route('donation.store') }}" enctype="multipart/form-data" class="card">
        @csrf
        <div class="card-body row">
            <div class="col-md-6">
                <x-dashboard.field name="kind" label="Jenis" type="select" required :options="App\Models\Donation::KINDS" value="tunai" />
                <x-dashboard.field name="donor_name" label="Nama donatur" required />
                <div class="form-check mb-6"><input class="form-check-input" type="checkbox" name="is_anonymous" value="1" id="is_anonymous" @checked(old('is_anonymous'))><label class="form-check-label" for="is_anonymous">Tampilkan sebagai "Hamba Allah" (di bukti & rekap)</label></div>
                <x-dashboard.field name="phone" label="Telepon / WhatsApp" />
                <x-dashboard.field name="email" label="Email" type="email" help="Bila diisi, bukti terima dikirim otomatis setelah voucher terposting." />
            </div>
            <div class="col-md-6">
                <x-dashboard.field name="amount" label="Jumlah / nilai wajar barang (Rp)" type="number" required min="1" step="1" />
                <x-dashboard.field name="transferred_at" label="Tanggal diterima" type="date" required :value="today()->toDateString()" />
                <x-dashboard.field name="coa_bank_account_id" label="Diterima di akun" type="select" required :options="$accounts->mapWithKeys(fn ($a) => [$a->id => $a->label()])->all()" help="Kas/bank untuk tunai & transfer; akun persediaan/aset untuk barang." />
                <x-dashboard.field name="purpose" label="Peruntukan" type="select" required :options="collect(App\Models\Donation::PURPOSES)->map(fn ($p) => $p[0])->all()" value="umum" />
                <x-dashboard.field name="item_description" label="Uraian barang" help="Wajib untuk donasi barang." />
                <x-dashboard.field name="proof" label="Bukti (foto/PDF)" type="file" accept="image/*,application/pdf" />
                <x-dashboard.field name="message" label="Pesan/doa donatur" type="textarea" />
            </div>
        </div>
        <div class="card-footer d-flex flex-wrap gap-3 justify-content-end align-items-center">
            @can('donation-verify')<label class="form-check me-auto"><input class="form-check-input" type="checkbox" name="verify_now" value="1" checked> <span class="form-check-label">Langsung verifikasi (buat voucher untuk disetujui Bendahara)</span></label>@endcan
            <a href="{{ route('donation.index') }}" class="btn btn-light">Batal</a>
            <button class="btn btn-primary">Simpan</button>
        </div>
    </form>
</x-dashboard.page>
