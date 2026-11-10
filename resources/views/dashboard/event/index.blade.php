<x-dashboard.page title="Agenda" :crumbs="['Kegiatan', 'Agenda']">
    <x-slot name="style"><link rel="stylesheet" href="{{ asset('plugins/custom/fullcalendar/fullcalendar.bundle.css') }}"></x-slot>
    <div class="card mb-5">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Kalender</div>
            <div class="card-toolbar gap-2">
                @can('agenda-view')<a class="btn btn-sm btn-light" href="{{ route('event.selawase') }}"><i class="fa fa-user-clock"></i> Laporan Sedulur Selawase</a>@endcan
                @can('agenda-create')<a class="btn btn-sm btn-primary" href="{{ route('event.create') }}"><i class="fa fa-plus"></i> Tambah Agenda</a>@endcan
            </div>
        </div>
        <div class="card-body"><div id="calendar"></div></div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title fs-4 fw-bolder">Daftar Agenda</div>
            <div class="card-toolbar gap-2">
                <select class="form-select form-select-sm w-auto" name="type" data-dt-filter="datatable" aria-label="Filter jenis"><option value="">Semua jenis</option>@foreach (App\Models\Event::TYPES as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
                <select class="form-select form-select-sm w-auto" name="status" data-dt-filter="datatable" aria-label="Filter status"><option value="">Semua status</option>@foreach (App\Models\Event::STATUSES as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
            </div>
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('event.datatable')" :order="[[3, 'desc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'title', 'title' => 'Judul'],
                ['data' => 'type', 'title' => 'Jenis', 'searchable' => false],
                ['data' => 'starts_at', 'title' => 'Mulai', 'searchable' => false],
                ['data' => 'audience', 'title' => 'Sasaran', 'searchable' => false],
                ['data' => 'seats', 'title' => 'Peserta', 'orderable' => false, 'searchable' => false],
                ['data' => 'status', 'title' => 'Status', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
    <x-slot name="javascript">
        <script src="{{ asset('plugins/custom/fullcalendar/fullcalendar.bundle.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                    initialView: window.innerWidth < 768 ? 'listMonth' : 'dayGridMonth', locale: 'id', firstDay: 1, height: 'auto',
                    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listMonth' },
                    buttonText: { today: 'Hari ini', month: 'Bulan', list: 'Daftar' },
                    events: @json(route('event.calendar')),
                });
                calendar.render();
            });
        </script>
    </x-slot>
</x-dashboard.page>
