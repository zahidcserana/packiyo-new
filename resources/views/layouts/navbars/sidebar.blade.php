@php
$page = request()->route()->getName();
@endphp
<nav class="sidenav navbar navbar-vertical fixed-left navbar-expand-xs navbar-light bg-white p-0 scroll-wrapper" id="sidenav-main">
    <div class="scrollbar-inner d-flex justify-content-between flex-column">
        <div>
            <div class="sidenav-header d-flex align-items-center">
                <a class="navbar-brand" href="{{ route('home') }}">
                    <img width="150" class="mh-100" src="{{  threepl_logo() }}" alt="">
                </a>
                <div class="h-100">
                    <!-- Sidenav toggler -->
                    <div class="sidenav-toggler d-none d-xl-block {{ \Illuminate\Support\Arr::get($_COOKIE, 'sidenav-state') === 'pinned' ? 'active' : '' }}" data-action="sidenav-unpin" data-target="#sidenav-main">
                        <img src="{{ asset('img/chevron.svg') }}" alt="">
                    </div>
                </div>
            </div>
            <div class="sidenav-logo-icon">
                <a href="{{ route('home') }}">
                    <img src="{{ asset('img/logo-square.svg') }}" alt="">
                </a>
            </div>
            <div class="navbar-inner">
                <!-- Collapse -->
                <div class="collapse navbar-collapse" id="sidenav-collapse-main">
                    <!-- Nav items -->
                    <ul class="navbar-nav">
                        @include('layouts.navbars.nav_items.dashboard', compact('page'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('order.index') }}">
                                <i class="picon-inbox-light icon-lg"></i>
                                <span class="nav-link-text {{ (!empty($page) && ($page == 'order.index')) ? 'active_item' : '' }}">{{ __('Orders') }}</span>
                            </a>
                        </li>
                        @include('layouts.navbars.nav_items.inventory', compact('page'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('return.index') }}">
                                <i class="picon-undo-filled icon-lg"></i>
                                <span class="nav-link-text {{ (!empty($page) && ($page == 'return.index')) ? 'active_item' : '' }}">{{ __('Returns') }}</span>
                            </a>
                        </li>
                        @include('layouts.navbars.nav_items.inbound', compact('page'))
                        @include('layouts.navbars.nav_items.reports', compact('page'))
                    </ul>
                </div>
            </div>
        </div>

        <div class="flex-grow-1 d-flex flex-column justify-content-end">
            <div class="navbar-inner">
                <!-- Collapse -->
                <div class="collapse navbar-collapse">
                    <!-- Nav items -->
                    <ul class="navbar-nav">
                        @include('layouts.navbars.nav_items.packing', compact('page'))

                        @if(auth()->user()->id == '1' && !empty(config('tribird.base_url')))
                            <div class="d-flex">
                                @include('layouts.navbars.nav_items.settings')
                                @include('layouts.navbars.nav_items.orderChannels')
                            </div>
                        @else
                            @include('layouts.navbars.nav_items.settings')
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>

</nav>
