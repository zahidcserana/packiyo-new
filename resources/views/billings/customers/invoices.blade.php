@extends('layouts.app', ['title' => __('Billing'), 'submenu' => 'billings.menu'])

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Billing',
        'subtitle' => 'Clients / ' . $customer->contactInformation->name,
    ])

    <div class="container-fluid">
        @include('billings.menuLinks', ['active' => 'clients'])

        <div class="row">
            <div class="col col-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <h2 class="m-0 ml-2">{{ __('Invoices') }}</h2>
                        <a href="{{ route('billings.customers') }}" class="text-black font-sm font-weight-600 d-inline-flex align-items-center bg-white border-8 px-3 py-2">
                            <i class="picon-arrow-backward-filled icon-lg icon-black"></i>
                            {{ __('Back') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-datatable
        search-placeholder="Search Invoice"
        table-id="customer-invoices-table"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
        tableContainerClass="p-0"
        tableClass="p-0 pb-4"
        bulkEdit=true
    />

    @include('billings.invoices.billRecalculateModal')
@endsection

@push('js')
    <script>
        let customerId = {{$customer->id}};
        new BillingCustomers();
    </script>
@endpush
