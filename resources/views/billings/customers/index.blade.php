@extends('layouts.app', ['title' => __('Billing'), 'submenu' => 'billings.menu'])

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Billing',
        'subtitle' => 'Customers'
    ])

    <div class="container-fluid">
        @include('billings.menuLinks', ['active' => 'customers'])
    </div>

    <x-datatable
            search-placeholder="Search Customer"
            table-id="customers-table"
            datatableOrder="{!! json_encode($datatableOrder) !!}"
            tableContainerClass="p-0 slim-table"
            tableClass="p-0 pb-5"
    />

    <div class="row d-none">
        <div class="col col-12">
            <div class="card table-card">
                <div class="card-header">
                    <div class="m-1">
                        <div class="row">
                            <form class="col-sm-6 create_form" method="POST" action="{{route('invoices.batchStore')}}">
                                @csrf
                                <fieldset>
                                    <div class="form-group md-3">
                                        <div class="d-inline-block">
                                            <label>{{__('Start Date')}}</label>
                                            <input
                                                type="text"
                                                name="start_date"
                                                id="date_to_compare"
                                                class="datetimepicker form-control w-auto h-auto"
                                                autocomplete="off"
                                                placeholder="Start Date"
                                                value={{old('start_date')}}
                                            >
                                        </div>
                                        <div class="d-inline-block">
                                            <label>{{__('End Date')}}</label>
                                            <input
                                                type="text"
                                                name="end_date"
                                                id="date_to"
                                                class="datetimepicker form-control w-auto h-auto"
                                                placeholder="End Date"
                                                autocomplete="off"
                                                value={{old('end_date')}}
                                            >
                                        </div>
                                    </div>
                                    <input
                                        class="customers_selectables"
                                        type="hidden"
                                        name="store_customers_selected"
                                        data-total-class="create-total"
                                        value="{{old('store_customers_selected', '[]')}}">
                                </fieldset>
                                <button type="submit" class="btn bg-logoOrange mx-auto px-5 font-weight-700 mt-5 change-tab text-white">{{ __('Create Invoices') }}</button>

                                <input type="button" value="Select All" class="btn btn-secondary mx-auto px-5 font-weight-700 mt-5 change-tab select-all">

                                <input type="button" value="Reset Selection" class="btn btn-danger mx-auto px-5 font-weight-700 mt-5 change-tab text-white reset">
                            </form>
                            <form class="col-sm-6 recalculate_form" method="POST" action="{{route('invoices.batchRecalculate')}}"
                                  style="display: none">
                                @csrf
                                <fieldset>
                                    <div class="form-group md-3">
                                        <label>{{__('Dates Between')}}</label>
                                        <input
                                            type="text"
                                            name="dates_between"
                                            class="table-datetimepicker table_filter form-control w-auto h-auto"
                                            placeholder="Dates between"
                                            value={{old('dates_between')}}
                                        >
                                    </div>
                                    <input class="customers_selectables"
                                           type="hidden"
                                           name="recalculate_customers_selected"
                                           data-total-class="recalculate-total"
                                           value="{{old('recalculate_customers_selected', '[]')}}">
                                </fieldset>
                                <button type="submit" class="btn bg-logoOrange mx-auto px-5 font-weight-700 mt-5 change-tab text-white">{{ __('Recalculate Invoices') }}</button>

                                <input type="button" value="Select All" class="btn btn-secondary select-all">

                                <input type="button" value="Reset Selection" class="btn btn-danger reset">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script>
        new BillingCustomers();
    </script>
@endpush
