@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => __('Shipments'),
        'subtitle' => __('Boxes'),
        'buttons' => [
            [
                'title' => __('Add shipping box'),
                'href' => route('shipping_box.create')
            ]
        ],
    ])

    <x-datatable
        search-placeholder="{{ __('Search boxes') }}"
        table-id="shipping-box-table"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
        table-class="table-hover"
    >
        <x-slot name="tableActions">
            <div class="mr-0 px-2">
                <a href="#" title="{{ __('Import Shipping Box') }}" data-toggle="modal" data-target="#import-shipping-box-modal">
                    <i class="picon-upload-light icon-lg"></i>
                </a>
            </div>
            <div class="mr-0 px-2">
                <a href="#" title="{{ __('Export Shipping Box') }}" data-toggle="modal" data-target="#export-shipping-box-modal">
                    <i class="picon-archive-light icon-lg"></i>
                </a>
            </div>
        </x-slot>
    </x-datatable>

    @include('shared.modals.components.shipping_box.importCsv')
    @include('shared.modals.components.shipping_box.exportCsv')
@endsection

@push('js')
    <script>
        new ShippingBox()
    </script>
@endpush

