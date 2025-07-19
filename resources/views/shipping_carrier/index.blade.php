@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Settings',
        'subtitle' => 'Carriers',
        'buttons' => [
            [
                'title' => __('Add Carrier'),
                'href' => '#',
                'data-toggle' => 'modal',
                'data-target' => '#carrierListModal',
                'className' => 'add-carrier-btn'
            ]
        ]
    ])

    <x-datatable
        search-placeholder="{{ __('Search carriers') }}"
        table-id="shipping-carrier-table"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
    />

    @include('shared.modals.carrierList')
    @include('shared.modals.carrierDisconnection')
@endsection

@push('js')
    <script>
        new ShippingCarrier()
    </script>
@endpush
