@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => __('Returns'),
        'subtitle' => __('Manage'),
        'buttons' => [
            [
                'title' => __('Add Return'),
                'href' =>  route('return.create'),
            ]
        ]
    ])

    <x-datatable
        search-placeholder="Search returns"
        table-id="returns-table"
        filters="local"
        filter-menu="shared.collapse.forms.returns"
        :data="$data"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
        bulkEdit=true
    >
        <x-slot name="tableActions">
            <div class="mr-0 px-2">
                <a href="#" title="{{ __('Export CSV') }}" data-toggle="modal"
                   data-target="#export-returns-modal">
                    <i class="picon-archive-light icon-lg"></i>
                </a>
            </div>
        </x-slot>
    </x-datatable>

    <div class="modal fade confirm-dialog" id="return-show" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        </div>
    </div>

    <div class="modal fade confirm-dialog" id="return-status" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        </div>
    </div>

    @include('shared.modals.components.returns.exportCsv')
@endsection

@push('js')
    <script>
        new ReturnOrder('{{$keyword}}')
    </script>
@endpush
