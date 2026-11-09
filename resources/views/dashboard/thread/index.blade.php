<x-dashboard.page title="Forum Diskusi" :crumbs="['Postingan', 'Forum']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">{{ auth()->user()->can('moderate', App\Models\Thread::class) ? 'Semua Diskusi' : 'Diskusi Saya' }}</div>
            <div class="card-toolbar gap-2">
                @can('moderate', App\Models\Thread::class)
                    <a class="btn btn-sm btn-light" href="{{ route('taxonomy.index', 'kategori-forum') }}"><i class="fa fa-folder"></i> Kategori</a>
                    <a class="btn btn-sm btn-light" href="{{ route('report.index') }}"><i class="fa fa-flag"></i> Laporan</a>
                @endcan
                <a class="btn btn-sm btn-light" href="{{ route('site.forum') }}" target="_blank"><i class="fa fa-external-link-alt"></i> Forum publik</a>
                @can('create', App\Models\Thread::class)<a class="btn btn-sm btn-light-primary" href="{{ route('thread.create') }}"><i class="fa fa-plus"></i> Diskusi baru</a>@endcan
            </div>
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('thread.datatable')" :order="[[5, 'desc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'title', 'name' => 'threads.title', 'title' => 'Judul'],
                ['data' => 'user.name', 'title' => 'Penulis'],
                ['data' => 'comments_count', 'title' => 'Balasan', 'searchable' => false],
                ['data' => 'flags', 'title' => '', 'orderable' => false, 'searchable' => false],
                ['data' => 'last_activity_at', 'name' => 'threads.last_activity_at', 'title' => 'Aktivitas terakhir', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
