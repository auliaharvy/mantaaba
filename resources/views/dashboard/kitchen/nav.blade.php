<div class="d-flex flex-wrap gap-2 mb-5">
    @foreach (['kitchen.index' => 'Hari ini', 'kitchen.plans' => 'Rencana mingguan', 'kitchen.needs' => 'Daftar belanja', 'kitchen.menus' => 'Resep/menu', 'kitchen.report' => 'Laporan bulanan'] as $route => $label)
        <a href="{{ route($route) }}" class="btn btn-sm {{ request()->routeIs($route) ? 'btn-primary' : 'btn-light' }}">{{ $label }}</a>
    @endforeach
</div>
