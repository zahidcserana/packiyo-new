@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => __('Inbound'),
        'subtitle' => __('Transfer Orders'),
        'buttons' => [
            [
                'title' => __('Create Transfer Order'),
                'href' => route('order.create')
            ]
        ]
    ])

    <x-datatable
        search-placeholder="Search transfer orders"
        table-id="transfer-orders-table"
        filters="local"
        filter-menu="shared.collapse.forms.transfer_orders"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
    />
@endsection

@push('js')
    <script>
        new TransferOrder()
    </script>
@endpush

