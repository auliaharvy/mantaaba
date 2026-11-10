<x-dashboard.page title="Verifikasi Anggota" :crumbs="['Anggota', 'Verifikasi']">
    <div class="card mw-500px">
        <div class="card-body d-flex gap-5 align-items-center">
            @if ($member->photoUrl())<img src="{{ $member->photoUrl() }}" alt="" class="w-75px h-100px rounded object-fit-cover">@endif
            <div>
                <div class="fs-3 fw-bolder">{{ $member->name }}</div>
                <div class="text-muted">{{ $member->member_number }} · {{ App\Models\Member::CATEGORIES[$member->category] }}{{ $member->batch ? ' · ' . $member->batch->code : '' }}</div>
                <span class="badge {{ $member->status === 'aktif' ? 'badge-success' : 'badge-light-danger' }} mt-2">{{ App\Models\Member::STATUSES[$member->status] }}</span>
                @can('view', $member)<div class="mt-3"><a href="{{ route('member.show', $member) }}" class="btn btn-sm btn-light-primary">Detail</a></div>@endcan
            </div>
        </div>
    </div>
</x-dashboard.page>
