@extends('layouts.app', ['title' => __('Invoices')])

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Billing',
        'subtitle' => 'Invoices',
        'buttons' => [[
            'title' => __('New Invoice'),
            'href' => '#',
            'data-toggle' => 'modal',
            'data-target' => '#batch-invoices-modal'
        ]]
    ])

    <div class="container-fluid">
        @include('billings.menuLinks', ['active' => 'invoices'])

        <div class="row">
            <div class="col col-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <h2 class="m-0 ml-2">{{ __('Invoices') }}</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-datatable
        search-placeholder="Search Customer"
        table-id="invoices-table"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
        tableContainerClass="p-0 slim-table"
        tableClass="p-0 pb-5"
    />
    @include('billings.invoices.batchInvoicesModal')
@endsection

@push('js')
    <script>
        new Invoices();
    </script>
@endpush
