<x-dashboard.page :title="'Siklus Baru · ' . $plot->code" :crumbs="['Operasional', 'Kebun & Kolam', 'Siklus baru']">
    <form method="POST" action="{{ route('farm.cycle-store', $plot) }}" class="card mw-700px">
        @csrf
        <div class="card-body row">
            <div class="col-md-6"><x-dashboard.field name="commodity" label="Komoditas" required :placeholder="$plot->type === 'kolam' ? 'Lele' : 'Cabai rawit'" /></div>
            <div class="col-md-6"><x-dashboard.field name="variety" label="Varietas" /></div>
            <div class="col-md-6"><x-dashboard.field name="started_at" label="Mulai" type="date" required :value="today()->toDateString()" /></div>
            <div class="col-md-6"><x-dashboard.field name="expected_harvest_at" label="Perkiraan panen" type="date" /></div>
            <div class="col-md-6"><x-dashboard.field name="initial_qty" label="{{ $plot->type === 'kolam' ? 'Jumlah tebar' : 'Jumlah bibit/benih' }}" type="number" step="0.01" min="0" /></div>
            <div class="col-md-6"><x-dashboard.field name="initial_unit" label="Satuan" :placeholder="$plot->type === 'kolam' ? 'ekor' : 'batang'" /></div>
            <div class="col-md-6"><x-dashboard.field name="target_yield" label="Target panen" type="number" step="0.01" min="0" /></div>
            <div class="col-md-6"><x-dashboard.field name="target_unit" label="Satuan target" placeholder="kg" /></div>
            <div class="col-12"><x-dashboard.field name="notes" label="Catatan" type="textarea" /></div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('farm.index') }}" class="btn btn-sm btn-light">Batal</a>
            <button class="btn btn-sm btn-primary">Mulai siklus</button>
        </div>
    </form>
</x-dashboard.page>
