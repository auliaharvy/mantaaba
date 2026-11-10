@php
    $user = auth()->user();
@endphp
<div id="kt_aside" class="aside" data-kt-drawer="true" data-kt-drawer-name="aside"
    data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true"
    data-kt-drawer-width="{default:'200px', '300px': '225px'}" data-kt-drawer-direction="start"
    data-kt-drawer-toggle="#kt_aside_toggle" data-kt-sticky="true" data-kt-sticky-name="aside-sticky"
    data-kt-sticky-offset="{default: false, lg: '1px'}" data-kt-sticky-width="{lg: '225px'}" data-kt-sticky-left="auto"
    data-kt-sticky-top="94px" data-kt-sticky-animation="false" data-kt-sticky-zindex="95">
    <div class="hover-scroll-y my-5 my-lg-5 w-100 ps-4 ps-lg-0 pe-4 me-1" id="kt_aside_menu_wrapper"
        data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-height="auto"
        data-kt-scroll-dependencies="#kt_header" data-kt-scroll-wrappers="#kt_aside" data-kt-scroll-offset="5px">

        {{-- Menu dari config('mantaaba.menu') lewat App\Support\Menu: tautan utama + modul (accordion, terbuka bila aktif) --}}
        <div class="menu menu-column menu-active-bg menu-hover-bg menu-title-gray-700 fs-6 menu-rounded w-100" id="#kt_aside_menu" data-kt-menu="true">
            @foreach (App\Support\Menu::top($user) as $item)
                <x-dashboard.aside.single_menu :linkClass="App\Support\Menu::isActive($item) ? 'active' : ''" :title="$item['title']" :url="route($item['route'], $item['params'] ?? [])">
                    <x-slot name="icon"><i class="fa {{ $item['icon'] }} fs-5"></i></x-slot>
                </x-dashboard.aside.single_menu>
            @endforeach
            <div class="menu-item pt-4"><div class="menu-content pb-2"><span class="menu-section text-muted text-uppercase fs-8 fw-bolder">Modul</span></div></div>
            @foreach (App\Support\Menu::modules($user) as $module)
                @php
                    $open = App\Support\Menu::isOpen($module);
                @endphp
                <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ $open ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon"><i class="fa {{ $module['icon'] }} fs-5"></i></span>
                        <span class="menu-title">{{ $module['title'] }}</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('overview.show') && request()->route('key') === $module['key'] ? 'active' : '' }}" href="{{ route('overview.show', $module['key']) }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">Ringkasan</span>
                            </a>
                        </div>
                        @foreach ($module['items'] as $item)
                            <div class="menu-item">
                                <a class="menu-link {{ App\Support\Menu::isActive($item) ? 'active' : '' }}" href="{{ route($item['route'], $item['params'] ?? []) }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span class="menu-title">{{ $item['title'] }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
