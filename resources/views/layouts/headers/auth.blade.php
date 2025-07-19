<div class="header bg-lightGrey py-2 py-md-3">
    <div class="container-fluid">
        <div class="row {{ isset($tabs) ? 'with-tabs' : '' }}">
            <div class="{{ !empty($buttons) ? 'col-6' : 'col-12' }} col-lg-6">
                <div class="header-body text-black">
                    <span class="font-weight-600 font-text-lg mr--2">
                        {!! ($title ? ($title . ' ') : '') . (! empty($subtitle) || isset($tabs) ? '/ ' : '') !!}
                    </span>
                    <span class="font-weight-400 font-md">
                        {{ $subtitle ?? (isset($tabs) ? collect($tabs)->where('route', 'current')->first()['name'] : '') }}
                    </span>
                </div>
            </div>
            <div class="col-12 col-lg-6 d-flex justify-content-end align-items-center">
                @if (isset($buttons))
                    @foreach ($buttons as $button)
                        <a
                            href="{{ $button['href'] ?? '#' }}"
                            class="btn bg-logoOrange px-lg-5 text-white float-right @if (isset($button['className'])){{ $button['className'] }}@endif"
                            @if (isset($button['data-toggle'], $button['data-target']))
                                data-toggle="{{ $button['data-toggle'] }}"
                                data-target="{{ $button['data-target'] }}"
                            @endif
                        >
                            {{ $button['title'] }}
                        </a>
                    @endforeach
                @elseif (isset($tabs))
                    <ul class="nav">
                        @foreach($tabs as $tab)
                            <li class="nav-item {{ $tab['route'] === 'current' ? 'active' : '' }}">
                                <a
                                    class="nav-link font-weight-600 font-sm text-black"
                                    aria-current="page"
                                    href="{{ $tab['route'] === 'current' ? '#' : $tab['route'] }}"
                                >
                                    {{ $tab['name'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if(!empty($headline))
                    <div class="header-body text-black edit-page-headline">
                        <span class="font-weight-600 font-text-lg mr-2">
                            {{ $headline }}
                        </span>
                    </div>
                @endif

                <a
                    href="#"
                    id="bulk-edit-btn"
                    class="btn bg-logoOrange mx-1 px-lg-5 text-white float-right"
                    data-toggle="modal"
                    data-target="#bulk-edit-modal"
                    hidden
                >
                    {{ __('Bulk Edit') }}
                </a>
                <a
                    href="#"
                    id="bulk-delete-btn"
                    class="btn bg-red mx-1 px-lg-5 text-white float-right"
                    hidden
                >
                    {{ __('Bulk Delete') }}
                </a>

                <form id="bulk-print-form" action="{{ route('bulk_print') }}" target="_blank" method="POST">
                    @csrf
                    <div id="bulk-print-inputs" class="d-none"></div>
                    <button
                        id="bulk-print-btn"
                        class="btn bg-logoOrange mx-1 px-lg-5 text-white float-right"
                        hidden
                    >
                        {{ __('Bulk Print') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
