{{-- Beranda: peluncur modul. Angka & grafik ada di halaman ringkasan tiap modul. --}}
<x-dashboard.page title="Beranda" :crumbs="[]">
    <div class="d-flex align-items-center gap-4 mb-6">
        <img src="{{ App\Models\Setting::current()->imageUrl('logo') }}" alt="" class="h-50px">
        <div>
            <div class="fs-3 fw-bolder">Assalamu'alaikum, {{ $user->name }}</div>
            <div class="text-muted fs-7">Pilih modul untuk melihat ringkasan dan menu kerjanya.</div>
        </div>
    </div>

    @foreach ($announcements as $announcement)
        <div class="alert {{ $announcement->is_pinned ? 'alert-primary' : 'alert-light-primary' }} d-flex gap-4 mb-5 py-4">
            <i class="fa fa-bullhorn fs-2 mt-1"></i>
            <div><div class="fw-bolder">{{ $announcement->title }}</div><div class="fs-7" style="white-space: pre-line">{{ \Illuminate\Support\Str::limit($announcement->body, 280) }}</div></div>
        </div>
    @endforeach

    <div class="row g-5">
        @foreach ($modules as $module)
            <div class="col-6 col-md-4 col-xl-3">
                <a href="{{ route('overview.show', $module['key']) }}" class="card card-flush h-100 hoverable text-center">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center py-8 px-4">
                        <span class="symbol symbol-65px mb-4"><span class="symbol-label bg-light-primary"><i class="fa {{ $module['icon'] }} fs-1 text-primary"></i></span></span>
                        <span class="fs-5 fw-bolder text-dark">{{ $module['title'] }}</span>
                        <span class="fs-8 text-muted mt-1">{{ $module['description'] }}</span>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</x-dashboard.page>
