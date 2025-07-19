@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Settings',
        'subtitle' => 'Location Types',
        'buttons' => [
            [
                'title' => __('Add location type'),
                'href' => route('location_type.create')
            ]
        ]
    ])

    <x-datatable
        search-placeholder="{{ __('Search types') }}"
        table-id="location-type-table"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
        :bulk-delete="true"
        :bulk-delete-route="route('location_type.bulk_delete')"
        table-class="table-hover"
    >
        <x-slot name="tableActions">
            <div class="mr-0 px-2">
                <a href="#" title="{{ __('Import Location Type') }}" data-toggle="modal" data-target="#import-location-type-modal">
                    <i class="picon-upload-light icon-lg"></i>
                </a>
            </div>
            <div class="mr-0 px-2">
                <a href="#" title="{{ __('Export Location Type') }}" data-toggle="modal" data-target="#export-location-type-modal">
                    <i class="picon-archive-light icon-lg"></i>
                </a>
            </div>
        </x-slot>
    </x-datatable>

    @include('shared.modals.components.location_type.importCsv')
    @include('shared.modals.components.location_type.exportCsv')
@endsection

@push('js')
    <script>
        new LocationType()
    </script>
@endpush

