<x-dashboard.page title="Pesan Masuk" :crumbs="['Website', 'Pesan Masuk']">
    <div class="card">
        <div class="card-header"><div class="card-title fs-3 fw-bolder">Pesan "Hubungi Kami" <span class="badge badge-light-danger ms-3">{{ $unread }} belum dibaca</span></div></div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('contact-message.datatable')" :order="[[1, 'desc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'created_at', 'title' => 'Waktu', 'searchable' => false],
                ['data' => 'name', 'title' => 'Nama'],
                ['data' => 'email', 'title' => 'Email'],
                ['data' => 'subject', 'title' => 'Perihal'],
                ['data' => 'state', 'title' => '', 'orderable' => false, 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
