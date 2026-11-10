<ul class="nav nav-pills mb-5 gap-1">
    @foreach (['coa' => ['Bagan akun', 'coa.index'], 'party' => ['Pihak', 'party.index'], 'center' => ['Pusat biaya', 'cost-center.index']] as $key => [$label, $route])
        <li class="nav-item"><a href="{{ route($route) }}" class="nav-link btn btn-sm btn-active-light-primary py-2 {{ $active === $key ? 'active' : '' }}">{{ $label }}</a></li>
    @endforeach
</ul>
