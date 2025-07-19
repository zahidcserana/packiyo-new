@extends('layouts.app', ['title' => __('Billing'), 'submenu' => 'billings.menu'])

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Billing',
        'subtitle' => '#' . $batch->id . ' ' . __('Bulk Invoice Batch'),
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
        @include('billings.menuLinks', ['active' => 'invoices'])

        <div class="row">
            <div class="col col-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <h2 class="m-0 ml-2">#{{ $batch->id }} {{ __('Bulk Invoice Batch') }}</h2>

                        <div class="row">
                            <div class="col d-flex">
                                @if(!$batch->is_finalized)
                                    <form
                                        method="POST"
                                        action="{{route('bulk_invoice_batches.finalize', ['bulk_invoice_batch' => $batch])}}"
                                        class="d-flex"
                                    >
                                        @method('PATCH')
                                        @csrf
                                        <button type="submit" class="table-icon-button" data-confirm-action="{{ __('Are you sure you want to finalize this batch?') }}">
                                            <i class="picon-check-circled-light icon-lg" title={{ __('Finalize') }}></i>
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        action="{{route('bulk_invoice_batches.recalculate', ['bulk_invoice_batch' => $batch])}}"
                                        class="d-flex"
                                    >
                                        @method('PATCH')
                                        @csrf
                                        <button type="submit" class="table-icon-button recalculate">
                                            <i class="picon-reload-light icon-lg" title={{ __('Recalculate') }}></i>
                                        </button>
                                    </form>

                                    <a target="_blank" href="{{route('bulk_invoice_batches.export', ['bulk_invoice_batch' => $batch])}}" class="d-flex align-items-center table-icon-button"><i class="picon-printer-light icon-lg" title="Export"></i></a>

                                    <form
                                        method="POST"
                                        action="{{route('bulk_invoice_batches.destroy', ['bulk_invoice_batch' => $batch])}}"
                                        class="d-flex"
                                    >
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="table-icon-button">
                                            <i class="picon-trash-light icon-lg" title={{ __('Delete Batch') }}></i>
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('billings.invoices') }}" class="text-black font-sm font-weight-600 d-inline-flex align-items-center bg-white border-8 px-3 py-2">
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
        filters="local"
        filter-menu="shared.collapse.forms.batch-invoice-line-items"
        table-id="batch-invoices-line-items-table"
        :data="$data"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
        tableContainerClass="p-0 slim-table"
        tableClass="p-0 pb-5"
    />

    <input type="hidden" id="customer-id" name="customer_id" value="{{$customer->id}}">
    <input type="hidden" id="batch-id" name="batch_id" value="{{$batch->id}}">

    @include('billings.invoices.bulkAdHocModal')
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

        $(document).find('select:not(.custom-select)').select2();

        new BatchInvoicesItems();
    </script>
@endpush
