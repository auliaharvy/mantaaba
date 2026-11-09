{{--
    Tabel server-side (B12). columns: [['data' => 'kolom', 'title' => 'Judul', 'orderable' => bool, 'searchable' => bool]].
    Filter: elemen dengan data-dt-filter="{{ $id }}" dan atribut name ikut dikirim sebagai parameter.
--}}
@props(['url', 'columns', 'id' => 'datatable', 'order' => [[1, 'asc']]])
@php
    $dtColumns = array_map(fn ($c) => [
        'data' => $c['data'],
        'name' => $c['name'] ?? $c['data'],
        'orderable' => $c['orderable'] ?? ! in_array($c['data'], ['DT_RowIndex', 'action']),
        'searchable' => $c['searchable'] ?? ! in_array($c['data'], ['DT_RowIndex', 'action']),
        'className' => $c['class'] ?? ($c['data'] === 'action' ? 'text-end' : ''),
        'defaultContent' => '-', // relasi kosong (mis. log dari sistem tanpa pengguna) tidak memicu alert DataTables
    ], $columns);
@endphp
<table id="{{ $id }}" class="table table-sm table-row-bordered gy-5 gs-8 border rounded no-footer nowrap dataTable w-100">
    <thead>
        <tr class="border-gray-200 fw-bold bg-lighten fs-6">
            @foreach ($columns as $column)
                <th>{{ $column['title'] ?? '' }}</th>
            @endforeach
        </tr>
    </thead>
</table>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filters = '[data-dt-filter="{{ $id }}"]';
        const table = $('#{{ $id }}').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: @json($url),
                data: function (d) { $(filters).each(function () { d[this.name] = $(this).val(); }); }
            },
            columns: @json($dtColumns),
            order: @json($order),
            pagingType: 'full_numbers',
            language: {
                search: 'Cari:', lengthMenu: '_MENU_',
                emptyTable: 'Tidak ada data yang tersedia pada tabel ini',
                info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                infoEmpty: 'Tidak menampilkan data apapun', infoFiltered: '(disaring dari _MAX_ data)',
                loadingRecords: 'Memuat...', processing: 'Memproses...',
                zeroRecords: 'Tidak ada data yang cocok ditemukan'
            },
            dom: "<'row'<'col-sm-6 d-flex align-items-center'l><'col-sm-6 d-flex align-items-center justify-content-end'f>>" +
                "<'table-responsive'tr>" +
                "<'row'<'col-sm-6 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                "<'col-sm-6 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>"
        });
        $(document).on('change', filters, function () { table.ajax.reload(); });
    });
</script>
