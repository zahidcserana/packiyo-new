@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Settings',
        'subtitle' => 'Shipping Method Mapping',
        'buttons' => [
            [
                'title' => __('Add method'),
                'href' => route('shipping_method_mapping.create')
            ]
        ]
    ])

     <x-datatable
        search-placeholder="{{ __('Search methods') }}"
        table-id="shipping-method-mapping-table"
        :filters=false
        datatableOrder="{!! json_encode($datatableOrder) !!}"
        table-class="table-hover"
    />
@endsection

@push('js')
    <script>
        new ShippingMethodMapping()
    </script>
@endpush
