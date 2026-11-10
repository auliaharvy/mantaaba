<div class="d-flex align-items-stretch flex-shrink-0">
    {{-- Lonceng notifikasi (PRD 9.20) --}}
    @php($unread = Auth::user()->unreadNotifications()->latest()->take(8)->get())
    <div class="d-flex align-items-center ms-1 ms-lg-3">
        <div class="btn btn-icon btn-active-light-primary position-relative w-35px h-35px" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end" title="Notifikasi" aria-label="Notifikasi">
            <i class="fa fa-bell fs-3"></i>
            @if ($unread->isNotEmpty())<span class="badge badge-circle badge-danger position-absolute top-0 start-100 translate-middle w-20px h-20px fs-9">{{ min(99, Auth::user()->unreadNotifications()->count()) }}</span>@endif
        </div>
        <div class="menu menu-sub menu-sub-dropdown menu-column w-300px w-lg-375px" data-kt-menu="true">
            <div class="d-flex justify-content-between align-items-center px-6 py-4 border-bottom">
                <span class="fw-bolder">Notifikasi</span>
                @if ($unread->isNotEmpty())
                    <form method="POST" action="{{ route('notification.read-all') }}">@csrf<button class="btn btn-sm btn-link p-0">Tandai dibaca</button></form>
                @endif
            </div>
            <div class="scroll-y mh-325px">
                @forelse ($unread as $notification)
                    <a href="{{ route('notification.read', $notification->id) }}" class="d-block px-6 py-3 border-bottom text-gray-800 bg-hover-light">
                        <div class="fw-bold">{{ $notification->data['title'] ?? 'Notifikasi' }}</div>
                        <div class="fs-7 text-muted">{{ $notification->data['body'] ?? '' }}</div>
                        <div class="fs-8 text-muted">{{ $notification->created_at->diffForHumans() }}</div>
                    </a>
                @empty
                    <div class="px-6 py-6 text-muted fs-7">Tidak ada notifikasi baru.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="d-flex align-items-stretch flex-shrink-0">
        <div class="d-flex align-items-center ms-lg-5" id="kt_header_user_menu_toggle">
            <div class="btn btn-active-light d-flex align-items-center bg-hover-light py-2 px-2 px-md-3"
                data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                <div class="d-none d-md-flex flex-column align-items-end justify-content-center me-2">
                    <span class="text-muted fs-7 fw-bold lh-1 mb-2">Halo,</span>
                    <span class="text-dark fs-base fw-bolder lh-1">{{ Auth::user()->name }}</span>
                </div>

                <div class="symbol symbol-30px symbol-md-40px">
                    @if (Storage::exists('public/user/' . Auth::user()->photo))
                    <img alt="img" src="{{ Storage::url('public/user/' . Auth::user()->photo) }}" />
                    @else
                    <img alt="img" src="{{ asset('media/avatars/blank.png') }}" />
                    @endif
                </div>
            </div>

            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-primary fw-bold py-4 fs-6 w-275px"
                data-kt-menu="true">
                <div class="menu-item px-3">
                    <div class="menu-content d-flex align-items-center px-3">

                        <div class="symbol symbol-50px me-5">
                            @if (Storage::exists('public/user/' . Auth::user()->photo))
                            <img alt="img" src="{{ Storage::url('public/user/' . Auth::user()->photo) }}" />
                            @else
                            <img alt="img" src="{{ asset('media/avatars/blank.png') }}" />
                            @endif
                        </div>

                        <div class="d-flex flex-column">
                            <div class="fw-bolder d-flex align-items-center fs-7">{{ Auth::user()->name }}</div>
                            <a href="#" class="fw-bold text-muted text-hover-primary fs-7 my-2">{{ Auth::user()->email
                                }}</a>

                            <x-dashboard.role-badge :user="Auth::user()" class="fw-bolder fs-7" />
                        </div>

                    </div>
                </div>

                <div class="separator my-2"></div>

                <div class="menu-item px-5">
                    <a href="{{ route('profile.index') }}" class="menu-link px-5">Profil Saya</a>
                </div>

                <div class="menu-item px-5">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <a class="menu-link px-5" onclick="event.preventDefault(); this.closest('form').submit();">
                            Keluar
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>