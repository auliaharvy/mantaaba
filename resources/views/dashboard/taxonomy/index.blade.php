@php($forum = $config['key'] === 'kategori-forum')
<x-dashboard.page :title="$config['label']" :crumbs="['Postingan', $config['label']]">
    <div class="row g-5">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><div class="card-title fs-4 fw-bolder">{{ $config['label'] }} ({{ $items->count() }})</div></div>
                <div class="card-body">
                    @forelse ($items as $item)
                        <div class="d-flex flex-wrap align-items-center gap-2 border-bottom py-3">
                            <form method="POST" action="{{ route('taxonomy.update', [$config['key'], $item->id]) }}" class="d-flex flex-wrap gap-2 flex-grow-1">
                                @csrf @method('PATCH')
                                <input class="form-control form-control-sm" style="max-width: 240px" name="name" value="{{ $item->name }}" required aria-label="Nama">
                                @if ($forum)
                                    <input class="form-control form-control-sm" style="max-width: 260px" name="description" value="{{ $item->description }}" placeholder="Deskripsi" aria-label="Deskripsi">
                                    <input class="form-control form-control-sm" style="max-width: 80px" type="number" min="0" name="sort_order" value="{{ $item->sort_order }}" aria-label="Urutan">
                                @endif
                                <button class="btn btn-sm btn-light-success" type="submit">Simpan</button>
                            </form>
                            <span class="text-muted fs-7">/{{ $item->slug }} · {{ $item->{$config['usage'] . '_count'} }} dipakai</span>
                            <form method="POST" action="{{ route('taxonomy.destroy', [$config['key'], $item->id]) }}" onsubmit="return confirm('Hapus {{ e($item->name) }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-icon btn-light-danger" title="Hapus"><i class="fa fa-trash"></i></button>
                            </form>
                        </div>
                    @empty
                        <div class="text-muted">Belum ada data.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <form method="POST" action="{{ route('taxonomy.store', $config['key']) }}" class="card">
                @csrf
                <div class="card-header"><div class="card-title fs-4 fw-bolder">Tambah</div></div>
                <div class="card-body">
                    <x-dashboard.field name="name" label="Nama" required />
                    @if ($forum)
                        <x-dashboard.field name="description" label="Deskripsi" />
                        <x-dashboard.field name="sort_order" label="Urutan" type="number" min="0" :value="0" />
                    @endif
                </div>
                <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Tambah</button></div>
            </form>
        </div>
    </div>
</x-dashboard.page>
