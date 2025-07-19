@extends('layouts.app', ['title' => __('Orders'), 'submenu' => 'orders.menu'])

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Billing',
        'subtitle' => 'Rate Cards',
        'buttons' => [
            [
                'title' => __('New Rate Card'),
                'href' => route('rate_cards.create')
            ]
        ]
    ])

    <div class="container-fluid">
        @include('billings.menuLinks', ['active' => 'rate-cards'])
    </div>

    <x-datatable
        search-placeholder="Search Rate Card"
        table-id="rate-cards-table"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
        tableContainerClass="p-0 slim-table"
        tableClass="p-0 pb-5"
    />
@endsection

@push('js')
    <script>
        new RateCard();
    </script>
@endpush
