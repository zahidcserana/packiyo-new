@extends('layouts.app')

@section('content')
    @if($customer->count() > 1 || ($customer->count() === 1  && $customer->first()->isNotChild()))
        @include('layouts.headers.auth', [
            'title' => 'Inventory',
            'subtitle' => 'Locations',
            'buttons' => [
                [
                    'title' => __('Add location'),
                    'href' => '#',
                    'data-toggle' => 'modal',
                    'data-target' => '#locationCreateModal',
                ]
            ]
        ])
    @else
        @include('layouts.headers.auth', [
                'title' => 'Inventory',
                'subtitle' => 'Locations',
            ])
    @endif

    <x-datatable
        search-placeholder="{{ __('Search locations') }}"
        table-id="locations-table"
        filters="local"
        filter-menu="shared.collapse.forms.locations"
        :data="$data"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
        :bulk-print="true"
        :bulk-delete="true"
        :bulk-delete-route="route('location.bulk_delete')"
        model-name="Location"
    >

        <x-slot name="tableActions">
            @if(isset($sessionCustomer) && $sessionCustomer->isNotChild())
                <div class="mr-0 px-2">
                    <a href="#" title="{{ __('Import Locations') }}" data-toggle="modal"
                    data-target="#import-locations-modal">
                        <i class="picon-upload-light icon-lg"></i>
                    </a>
                </div>
            @endif
            <div class="mr-0 px-2">
                <a href="#" title="{{ __('Export Locations') }}" data-toggle="modal"
                   data-target="#export-locations-modal">
                    <i class="picon-archive-light icon-lg"></i>
                </a>
            </div>
        </x-slot>
    </x-datatable>

    @if($customer->count() > 1 || ($customer->count() === 1  && $customer->first()->isNotChild()))
        @include('shared.modals.locationModals')
    @endif

    @include('shared.modals.components.location.importCsv')
    @include('shared.modals.components.location.exportCsv')
@endsection

@push('js')
    <script>
        new LocationForm()
    </script>
@endpush
