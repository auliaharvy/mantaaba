<x-dashboard.page :title="$event->title" :crumbs="['Kegiatan', 'Agenda', 'Kelola']">
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-5">
        <a class="btn btn-sm btn-light" href="{{ route('event.index') }}"><i class="fa fa-arrow-left"></i> Daftar agenda</a>
        @if ($event->status !== 'draft')<a class="btn btn-sm btn-light" href="{{ route('site.training.show', $event->slug) }}" target="_blank"><i class="fa fa-external-link-alt"></i> Lihat di situs</a>@endif
        @can('update', $event)
            <a class="btn btn-sm btn-light-info" href="{{ route('event.check-in', $event) }}"><i class="fa fa-qrcode"></i> Check-in</a>
            <a class="btn btn-sm btn-light" href="{{ route('event.participants.export', $event) }}"><i class="fa fa-download"></i> Export peserta</a>
            @if ($event->audience !== 'publik')<form method="POST" action="{{ route('event.participants.sync', $event) }}">@csrf<button class="btn btn-sm btn-light"><i class="fa fa-sync"></i> Sinkronkan peserta</button></form>@endif
            <form method="POST" action="{{ route('event.duplicate', $event) }}">@csrf<button class="btn btn-sm btn-light"><i class="fa fa-copy"></i> Duplikasi</button></form>
            <a class="btn btn-sm btn-light-success" href="{{ route('event.edit', $event) }}"><i class="fa fa-pen"></i> Ubah</a>
        @endcan
    </div>

    <div class="row g-5 mb-5">
        @foreach ([['Peserta', $event->registered_count . ($event->quota ? ' / ' . $event->quota : '')], ['Hadir', $event->present_count], ['Status', App\Models\Event::STATUSES[$event->status]]] as [$label, $value])
            <div class="col-6 col-lg-4"><div class="card h-100"><div class="card-body">
                <div class="text-muted fs-7">{{ $label }}</div><div class="fs-2x fw-bolder">{{ $value }}</div>
            </div></div></div>
        @endforeach
    </div>

    <div class="card mb-5">
        <div class="card-body d-flex flex-wrap gap-6 fs-7">
            <span><strong>Jenis:</strong> {{ App\Models\Event::TYPES[$event->type] }}{{ $event->skema ? ' · ' . $event->skema->code : '' }}</span>
            <span><strong>Jadwal:</strong> {{ $event->starts_at->translatedFormat('j M Y H:i') }} – {{ $event->ends_at->translatedFormat('j M Y H:i') }}</span>
            <span><strong>Sasaran:</strong> {{ App\Models\Event::AUDIENCES[$event->audience] }}{{ $event->batch ? ' ' . $event->batch->code : '' }}</span>
            <span><strong>Tempat:</strong> {{ App\Models\Event::MODES[$event->mode] }}{{ $event->location ? ' · ' . $event->location : '' }}</span>
        </div>
    </div>

    <div class="card mb-5">
        <div class="card-header">
            <div class="card-title fs-4 fw-bolder">Peserta</div>
            @can('update', $event)
                <div class="card-toolbar w-100 w-md-auto">
                    <form method="POST" action="{{ route('event.participants.store', $event) }}" class="d-flex flex-wrap gap-2 align-items-center w-100">
                        @csrf
                        <select id="add-participant" name="user_id" class="form-select form-select-sm" style="width: min(100%, 300px)" data-placeholder="Tambah peserta manual (ketik nama)" required></select>
                        <button class="btn btn-sm btn-light-primary" type="submit">Tambah</button>
                    </form>
                </div>
            @endcan
        </div>
        <div class="card-body p-lg-8">
            <div class="d-flex flex-wrap justify-content-between gap-3 mb-5">
                <select class="form-select form-select-sm w-auto" name="status" data-dt-filter="participants" aria-label="Filter status peserta">
                    <option value="">Semua status</option>
                    @foreach (App\Models\Participant::STATUSES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
                @can('update', $event)
                    <form method="POST" action="{{ route('event.attendance', $event) }}" id="attendance-form" class="d-flex gap-2">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm btn-light-success" name="present" value="1">Tandai hadir (terpilih)</button>
                        <button class="btn btn-sm btn-light" name="present" value="0">Batalkan hadir</button>
                    </form>
                @endcan
            </div>
            <x-dashboard.datatable id="participants" :url="route('event.participants.datatable', $event)" :order="[[4, 'asc']]" :columns="[
                ['data' => 'check', 'title' => '', 'orderable' => false, 'searchable' => false],
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'name', 'title' => 'Nama', 'orderable' => false],
                ['data' => 'contact', 'title' => 'Kontak', 'orderable' => false, 'searchable' => false],
                ['data' => 'status', 'name' => 'participants.status', 'title' => 'Status', 'searchable' => false],
                ['data' => 'attended_at', 'name' => 'participants.attended_at', 'title' => 'Hadir', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>

    @can('update', $event)
        <div class="row g-5">
            <div class="col-lg-6">
                <form method="POST" action="{{ route('event.announce', $event) }}" class="card h-100">
                    @csrf
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Kirim Pengumuman</div></div>
                    <div class="card-body">
                        <x-dashboard.field name="subject" label="Judul" required />
                        <x-dashboard.field name="message" label="Isi (dikirim ke email semua peserta)" type="textarea" required rows="4" />
                    </div>
                    <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Kirim email</button></div>
                </form>
            </div>
        </div>
    @endcan

    <x-slot name="javascript">
        <script>
            $('#add-participant').select2({
                minimumInputLength: 2,
                ajax: { url: '{{ route('event.participants.candidates', $event) }}', dataType: 'json', delay: 250, data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }) },
                language: { inputTooShort: () => 'Ketik minimal 2 huruf', noResults: () => 'Tidak ditemukan' }
            });
        </script>
    </x-slot>
</x-dashboard.page>
