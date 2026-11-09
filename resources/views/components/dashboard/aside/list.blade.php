<div id="kt_aside" class="aside" data-kt-drawer="true" data-kt-drawer-name="aside"
    data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true"
    data-kt-drawer-width="{default:'200px', '300px': '225px'}" data-kt-drawer-direction="start"
    data-kt-drawer-toggle="#kt_aside_toggle" data-kt-sticky="true" data-kt-sticky-name="aside-sticky"
    data-kt-sticky-offset="{default: false, lg: '1px'}" data-kt-sticky-width="{lg: '225px'}" data-kt-sticky-left="auto"
    data-kt-sticky-top="94px" data-kt-sticky-animation="false" data-kt-sticky-zindex="95">
    <div class="hover-scroll-y my-5 my-lg-5 w-100 ps-4 ps-lg-0 pe-4 me-1" id="kt_aside_menu_wrapper"
        data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-height="auto"
        data-kt-scroll-dependencies="#kt_header" data-kt-scroll-wrappers="#kt_aside" data-kt-scroll-offset="5px">

        <!-- Start::Aside Menu -->
        <div class="menu menu-column menu-active-bg menu-hover-bg menu-title-gray-700 fs-6 menu-rounded w-100"
            id="#kt_aside_menu" data-kt-menu="true">
            {{-- PRD 6.3: menu dari config/mantaaba.php; item hanya tampil bila diizinkan --}}
            @foreach (config('mantaaba.menu') as $group)
                @php($items = collect($group['items'])->filter(fn ($item) => (! isset($item['can']) || auth()->user()->can($item['can'])) && (! isset($item['role']) || auth()->user()->hasRole($item['role']))))
                @continue($items->isEmpty())
                @isset($group['title'])
                    <div class="menu-item pt-5">
                        <div class="menu-content pb-2"><span class="menu-section text-muted text-uppercase fs-7 fw-bolder">{{ $group['title'] }}</span></div>
                    </div>
                @endisset
                @foreach ($items as $item)
                    @php($active = request()->routeIs(...explode('|', $item['active'] ?? \Illuminate\Support\Str::beforeLast($item['route'], '.') . '.*')))
                    <x-dashboard.aside.single_menu :linkClass="$active ? 'active' : ''" :title="$item['title']" :url="route($item['route'], $item['params'] ?? [])">
                        <x-slot name="icon"><i class="fa {{ $item['icon'] }} fs-5"></i></x-slot>
                    </x-dashboard.aside.single_menu>
                @endforeach
            @endforeach
        </div>
        <!-- End::Aside Menu -->

    </div>
</div>
