@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Shipments',
        'subtitle' => 'Totes',
        'buttons' => [
            [
                'title' => __('Add Tote'),
                'href' => route('tote.create'),
            ],
            [
                'title' => __('Tote Log'),
                'href' => route('report.view', ['reportId' => 'tote_log']),
            ]
        ]
    ])

    <x-datatable
        search-placeholder="{{ __('Search totes') }}"
        table-id="totes-table"
        filters="local"
        filter-menu="shared.collapse.forms.totes"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
        :bulk-print="true"
        :bulk-delete="true"
        :bulk-delete-route="route('tote.bulk_delete')"
        model-name="Tote"
    >
        <x-slot name="tableActions">
            <div class="mr-0 px-2">
                <a href="#" title="{{ __('Import CSV') }}" data-toggle="modal"
                   data-target="#import-totes-modal">
                    <i class="picon-upload-light icon-lg"></i>
                </a>
            </div>
            <div class="mr-0 px-2">
                <a href="#" title="{{ __('Export CSV') }}" data-toggle="modal"
                   data-target="#export-totes-modal">
                    <i class="picon-archive-light icon-lg"></i>
                </a>
            </div>
        </x-slot>
    </x-datatable>

    @include('shared.modals.components.totes.importCsv')
    @include('shared.modals.components.totes.exportCsv')
@endsection

@push('js')
    <script>
        new Tote()
    </script>
@endpush
