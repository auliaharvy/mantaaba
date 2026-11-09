<x-dashboard.page title="Beranda" :crumbs="[]">
    <div class="card mb-5">
        <div class="card-body d-flex align-items-center gap-5">
            <img src="{{ App\Models\Setting::current()->imageUrl('logo') }}" alt="" class="h-60px">
            <div>
                <div class="fs-2 fw-bolder">Assalamu'alaikum, {{ $user->name }}</div>
                <div class="text-muted">Selamat datang di sistem {{ config('app.name') }}.</div>
            </div>
        </div>
    </div>

    @if ($cards)
        <div class="row g-5 mb-5">
            @foreach ($cards as [$label, $value, $icon, $url])
                <div class="col-sm-6 col-xl-3">
                    <a href="{{ $url }}" class="card card-flush h-100 hoverable">
                        <div class="card-body d-flex align-items-center gap-4">
                            <i class="fa {{ $icon }} fs-1 text-primary"></i>
                            <div><div class="fs-2hx fw-bolder text-dark">{{ number_format($value, 0, ',', '.') }}</div><div class="text-muted">{{ $label }}</div></div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <div class="card-header"><div class="card-title fs-4 fw-bolder">Agenda terdekat</div></div>
        <div class="card-body">
            @if ($event)
                <div class="fw-bolder fs-5">{{ $event->title }}</div>
                <div class="text-muted">{{ $event->starts_at?->translatedFormat('l, j F Y H:i') }} · {{ $event->location ?: App\Models\Event::MODES[$event->mode] }}</div>
                <a href="{{ route('site.training.show', $event->slug) }}" class="btn btn-sm btn-light-primary mt-4">Lihat agenda</a>
            @else
                <div class="text-muted">Belum ada agenda mendatang.</div>
            @endif
        </div>
    </div>
</x-dashboard.page>
