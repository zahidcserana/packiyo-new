@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Inventory',
        'subtitle' => 'Kits'
    ])

    <x-datatable
        searchPlaceholder="{{ __('Search kits') }}"
        tableId="kits-table"
        filters="{{ !empty(app('user')->getSelectedCustomers()) && app('user')->getSelectedCustomers()->count() > 1 ? 'local' : '' }}"
        filter-menu="shared.collapse.forms.kits"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
    >
        <x-slot name="tableActions">
            <div class="mr-0 px-2">
                <a href="#" title="{{ __('Import kits') }}" data-toggle="modal"
                   data-target="#import-kits-modal">
                    <i class="picon-upload-light icon-lg"></i>
                </a>
            </div>
            <div class="mr-0 px-2">
                <a href="#" title="{{ __('Export kits') }}" data-toggle="modal"
                   data-target="#export-kits-modal">
                    <i class="picon-archive-light icon-lg"></i>
                </a>
            </div>
        </x-slot>
    </x-datatable>

    @include('shared.modals.components.kits.importCsv')
    @include('shared.modals.components.kits.exportCsv')
@endsection

@push('js')
    <script>
        new Kit()
    </script>
@endpush
