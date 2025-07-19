@extends('layouts.app', ['title' => __('Billing'), 'submenu' => 'billings.menu'])

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Billing',
        'subtitle' => 'Clients / ' . $customer->contactInformation->name . ' / #' . $invoice->id . ' ' . __('Invoice Items'),
        'buttons' => [
            [
                'title' => __('New Ad hoc Charge'),
                'href' => '#',
                'data-toggle' => 'modal',
                'data-target' => '#add-hoc-modal',
            ]
        ]
    ])

    <div class="container-fluid">
        @include('billings.menuLinks', ['active' => 'clients'])

        <div class="row">
            <div class="col col-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <h2 class="m-0 ml-2">#{{ $invoice->id }} {{ __('Invoice Items') }}</h2>

                        <div class="row">
                            <div class="col d-flex">
                                @if(!$invoice->is_finalized)
                                    <form
                                        method="POST"
                                        action="{{route('invoices.finalize', ['invoice' => $invoice])}}"
                                        class="d-flex"
                                    >
                                        @csrf
                                        <button type="submit" class="table-icon-button" data-confirm-action="{{ __('Are you sure you want to finalize this invoice?') }}">
                                            <i class="picon-check-circled-light icon-lg" title={{ __('Finalize') }}></i>
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        action="{{route('invoices.recalculate', ['invoice' => $invoice])}}"
                                        class="d-flex"
                                    >
                                        @csrf
                                        <button type="submit" class="table-icon-button recalculate">
                                            <i class="picon-reload-light icon-lg" title={{ __('Recalculate') }}></i>
                                        </button>
                                    </form>

{{--                                    <form--}}
{{--                                        method="GET"--}}
{{--                                        action="{{route('invoices.generateCsv', ['invoice' => $invoice])}}"--}}
{{--                                        class="d-flex"--}}
{{--                                    >--}}
{{--                                        @csrf--}}
{{--                                        <button type="submit" class="table-icon-button">--}}
{{--                                            <i class="picon-printer-light icon-lg" title="Generate Csv"></i>--}}
{{--                                        </button>--}}
{{--                                    </form>--}}

{{--                                    @if($invoice->csv_url)--}}
{{--                                        <a href="{{route('invoices.downloadGeneratedCsv', ['invoice' => $invoice])}}" target="_blank" class="btn bg-logoOrange m-2 font-weight-700 change-tab text-white">{{ __('Download Generated Csv') }}</a>--}}
{{--                                    @endif--}}

                                    <a target="_blank" href="{{route('invoices.exportCsv', ['invoice' => $invoice])}}" class="d-flex align-items-center table-icon-button"><i class="picon-printer-light icon-lg" title="Export Invoice"></i></a>

                                    <form
                                        method="POST"
                                        action="{{route('invoices.destroy', ['invoice' => $invoice])}}"
                                        class="d-flex"
                                    >
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="table-icon-button recalculate">
                                            <i class="picon-trash-light icon-lg" title={{ __('Delete Invoice') }}></i>
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('billings.customers') }}/{{ $customer->id }}/invoices" class="text-black font-sm font-weight-600 d-inline-flex align-items-center bg-white border-8 px-3 py-2">
                                    <i class="picon-arrow-backward-filled icon-lg icon-black"></i>
                                    {{ __('Back') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-datatable
        search-placeholder="Search Invoice Item"
        table-id="invoice-line-items-table"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
        tableContainerClass="p-0 slim-table"
        tableClass="p-0 pb-5"
    />

    <input type="hidden" id="customer-id" name="customer_id" value="{{$customer->id}}">
    <input type="hidden" id="invoice-id" name="invoice_id" value="{{$invoice->id}}">

    @include('billings.invoices.adHocModal')
    @include('billings.invoices.editInvoiceLineItemModal')
@endsection

@push('js')
    <script>
        $(document).ready(function () {
            $('.recalculate').on('click', function () {

                $('a, button').hide()
                $(this).html('Recalculating').show().prop('disabled', true)
                $(this).closest('form').submit()
            })
        })

        new BillingInvoices();
    </script>
@endpush
