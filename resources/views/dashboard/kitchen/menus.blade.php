<x-dashboard.page title="Resep & Menu" :crumbs="['Operasional', 'Dapur', 'Menu']">
    @include('dashboard.kitchen.nav')
    <div class="card">
        <div class="card-header"><div class="card-title fs-5 fw-bolder">Menu</div>
            <div class="card-toolbar">@can('kitchen-create')<a href="{{ route('kitchen.menu-create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Menu</a>@endcan</div></div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-row-bordered align-middle gs-5 gy-3 mb-0 fs-7">
                <thead><tr class="fw-bold bg-light"><th>Menu</th><th>Kategori</th><th>Bahan per porsi</th><th></th></tr></thead>
                <tbody>
                    @forelse ($menus as $menu)
                        <tr class="{{ $menu->is_active ? '' : 'text-muted' }}">
                            <td class="fw-bold">{{ $menu->name }}</td>
                            <td>{{ App\Models\KitchenMenu::CATEGORIES[$menu->category] }}</td>
                            <td>{{ collect($menu->ingredients)->map(fn ($r) => ($items[$r['item_id']] ?? '?') . ' ' . App\Services\StockLedger::qty((float) $r['qty']))->implode(', ') ?: '-' }}</td>
                            <td class="text-end">@can('kitchen-update')<a href="{{ route('kitchen.menu-edit', $menu) }}" class="btn btn-sm btn-icon btn-light-success" title="Ubah"><i class="fa fa-pen"></i></a>@endcan</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-10">Belum ada menu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-dashboard.page>
