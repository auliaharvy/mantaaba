<x-dashboard.page title="Berita & Artikel" :crumbs="['Postingan', 'Berita & Artikel']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">{{ auth()->user()->can('publish', App\Models\Blog::class) ? 'Semua Tulisan' : 'Tulisan Saya' }}</div>
            <div class="card-toolbar gap-2">
                @can('publish', App\Models\Blog::class)
                    <a class="btn btn-sm btn-light" href="{{ route('taxonomy.index', 'kategori') }}"><i class="fa fa-folder"></i> Kategori</a>
                    <a class="btn btn-sm btn-light" href="{{ route('taxonomy.index', 'tag') }}"><i class="fa fa-tags"></i> Tag</a>
                @endcan
                @can('create', App\Models\Blog::class)
                    <a class="btn btn-sm btn-light-primary" href="{{ route('blog.create') }}"><i class="fa fa-plus"></i> Tulis</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-lg-8">
            <div class="row mb-5">
                <div class="col-md-4">
                    <select class="form-select form-select-sm" name="status" data-dt-filter="datatable" aria-label="Filter status">
                        <option value="">Semua status</option>
                        @foreach (App\Models\Blog::STATUSES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
            </div>
            <x-dashboard.datatable :url="route('blog.datatable')" :order="[[5, 'desc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'title', 'name' => 'blogs.title', 'title' => 'Judul'],
                ['data' => 'category.name', 'title' => 'Kategori'],
                ['data' => 'user.name', 'title' => 'Penulis'],
                ['data' => 'status', 'name' => 'blogs.status', 'title' => 'Status', 'searchable' => false],
                ['data' => 'updated_at', 'name' => 'blogs.updated_at', 'title' => 'Diubah', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
