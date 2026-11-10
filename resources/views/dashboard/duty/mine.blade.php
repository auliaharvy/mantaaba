<x-dashboard.page title="Piket Saya" :crumbs="['Piket Saya']">
    @error('checklist')<div class="alert alert-danger">{{ $message }}</div>@enderror
    <div class="card mb-5">
        <div class="card-header"><div class="card-title fs-4 fw-bolder">Piket hari ini</div>
            @if ($rate !== null)<div class="card-toolbar"><span class="badge badge-light-primary">Bulan ini {{ $rate }}% selesai</span></div>@endif</div>
        <div class="card-body">
            @forelse ($today as $duty)
                <div class="border rounded p-5 mb-4">
                    <div class="d-flex justify-content-between flex-wrap gap-2 mb-3"><span class="fw-bolder fs-5">{{ $duty->area->name }} · {{ App\Models\Duty::SHIFTS[$duty->shift] }}</span>
                        <span class="badge badge-light-{{ in_array($duty->status, App\Models\Duty::DONE) ? 'success' : 'primary' }}">{{ App\Models\Duty::STATUSES[$duty->status] }}</span></div>
                    @if ($duty->area->description)<p class="text-muted fs-7">{{ $duty->area->description }}</p>@endif
                    @if ($duty->status === 'terjadwal')
                        <form method="POST" action="{{ route('my-duty.complete', $duty) }}" enctype="multipart/form-data">
                            @csrf
                            @foreach ($duty->area->checklist ?? [] as $i => $item)
                                <label class="form-check form-check-custom mb-3"><input class="form-check-input" type="checkbox" name="checklist[]" value="{{ $item }}"> <span class="form-check-label fs-6">{{ $item }}</span></label>
                            @endforeach
                            <div class="row mt-4">
                                <div class="col-md-6"><x-dashboard.field name="photo" :id="'photo-' . $duty->id" label="Foto (opsional)" type="file" accept="image/*" capture="environment" /></div>
                                <div class="col-md-6"><x-dashboard.field name="note" :id="'note-' . $duty->id" label="Catatan" /></div>
                            </div>
                            <button class="btn btn-success w-100"><i class="fa fa-check"></i> Selesai</button>
                        </form>
                    @else
                        <div class="text-muted fs-7">Selesai {{ $duty->done_at?->format('H:i') }}{{ $duty->verified_at ? ' · diverifikasi' : '' }}</div>
                    @endif
                </div>
            @empty
                <p class="text-muted mb-0">Tidak ada piket hari ini.</p>
            @endforelse
        </div>
    </div>
    <div class="row g-5">
        <div class="col-lg-6"><div class="card h-100"><div class="card-header"><div class="card-title fs-5 fw-bolder">Jadwal berikutnya</div></div><div class="card-body">
            @forelse ($upcoming as $duty)<div class="d-flex gap-3 py-2 border-bottom"><span class="text-muted w-100px">{{ $duty->date->translatedFormat('D, j M') }}</span><span>{{ $duty->area->name }} · {{ App\Models\Duty::SHIFTS[$duty->shift] }}</span></div>
            @empty<p class="text-muted mb-0">Belum ada jadwal.</p>@endforelse
        </div></div></div>
        <div class="col-lg-6"><div class="card h-100"><div class="card-header"><div class="card-title fs-5 fw-bolder">Riwayat</div></div><div class="card-body">
            @forelse ($history as $duty)<div class="d-flex gap-3 py-2 border-bottom"><span class="text-muted w-100px">{{ $duty->date->translatedFormat('D, j M') }}</span><span class="flex-grow-1">{{ $duty->area->name }}</span><span class="badge badge-light{{ $duty->status === 'terlewat' ? '-danger' : '-success' }}">{{ App\Models\Duty::STATUSES[$duty->status] }}</span></div>
            @empty<p class="text-muted mb-0">Belum ada riwayat.</p>@endforelse
        </div></div></div>
    </div>
</x-dashboard.page>
