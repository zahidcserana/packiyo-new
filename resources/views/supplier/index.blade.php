@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Purchase Orders',
        'subtitle' => 'Manage Vendors',
        'buttons' => [[
            'title' => __('Add Vendor'),
            'href' => route('supplier.index') . '#open-modal'
        ]]
    ])

    <x-datatable
        searchPlaceholder="{{ __('Search vendor') }}"
        tableId="supplier-table"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
        bulkEdit=true
    >
        <x-slot name="tableActions">
            <div class="mr-0 px-2">
                <a href="#" title="{{ __('Import Vendors') }}" data-toggle="modal" data-target="#import-vendors-modal">
                    <i class="picon-upload-light icon-lg"></i>
                </a>
            </div>
            <div class="mr-0 px-2">
                <a href="#" title="{{ __('Export Vendors') }}" data-toggle="modal" data-target="#export-suppliers-modal">
                    <i class="picon-archive-light icon-lg"></i>
                </a>
            </div>
        </x-slot>
    </x-datatable>

    @include('shared.modals.components.vendor.importCsv')
    @include('shared.modals.components.vendor.exportCsv')
    @include('shared.modals.vendorModals')
@endsection

@push('js')
    <script>
        new Supplier()
    </script>
@endpush
