<x-dashboard.page title="Menu Situs" :crumbs="['Website', 'Menu']">
    @php($canUpdate = auth()->user()->can('cms-update'))
    <div class="alert alert-light-primary fs-7">Seret ikon <i class="fa fa-grip-vertical"></i> untuk mengubah urutan dalam satu tingkat, lalu klik <strong>Simpan urutan</strong>. Untuk memindah ke induk lain, ubah menu tersebut.</div>
    <form method="POST" action="{{ route('menu.reorder') }}" id="menu-order-form">
        @csrf
        <div class="row g-5">
            @foreach (App\Models\NavMenu::LOCATIONS as $location => $label)
                <div class="col-xl-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <div class="card-title fs-4 fw-bolder">{{ $label }} <span class="text-muted fs-7 ms-2">{{ $location === 'header' ? 'maks. 2 tingkat' : '1 tingkat' }}</span></div>
                            @can('cms-create')
                                <div class="card-toolbar"><a class="btn btn-sm btn-primary" href="{{ route('menu.create', ['location' => $location]) }}"><i class="fa fa-plus"></i> Tambah</a></div>
                            @endcan
                        </div>
                        <div class="card-body">
                            @php($items = $menus[$location] ?? collect())
                            @if ($items->isEmpty())
                                <p class="text-muted">Belum ada menu.</p>
                            @endif
                            <ul class="list-unstyled menu-sortable mb-0">
                                @foreach ($items as $item)
                                    <li class="menu-item mb-2" data-id="{{ $item->id }}">
                                        @include('dashboard.menu.row', ['item' => $item])
                                        @if ($location === 'header')
                                            <ul class="list-unstyled menu-sortable ms-10 mt-2">
                                                @foreach ($item->children as $child)
                                                    <li class="menu-item mb-2" data-id="{{ $child->id }}">@include('dashboard.menu.row', ['item' => $child])</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if ($canUpdate)
            <div class="text-end mt-5"><button type="submit" class="btn btn-sm btn-primary">Simpan urutan</button></div>
        @endif
    </form>
    <x-slot name="javascript">
        <script src="{{ asset('plugins/custom/draggable/draggable.bundle.js') }}"></script>
        <script>
            // urutan hanya di dalam satu tingkat (container yang sama)
            const sortable = new Sortable.default(document.querySelectorAll('.menu-sortable'), {
                draggable: '.menu-item', handle: '.menu-handle', mirror: { appendTo: 'body', constrainDimensions: true }
            });
            sortable.on('sortable:sort', (e) => { if (e.dragEvent.overContainer !== e.dragEvent.sourceContainer) e.cancel(); });
            document.getElementById('menu-order-form').addEventListener('submit', function () {
                this.querySelectorAll('input[name="order[]"]').forEach((el) => el.remove());
                this.querySelectorAll('.menu-item[data-id]').forEach((li) => {
                    if (li.classList.contains('draggable-mirror')) return;
                    const input = document.createElement('input');
                    input.type = 'hidden'; input.name = 'order[]'; input.value = li.dataset.id;
                    this.appendChild(input);
                });
            });
        </script>
    </x-slot>
</x-dashboard.page>
