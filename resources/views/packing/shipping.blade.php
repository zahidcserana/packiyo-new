@extends('layouts.app')

@section('content')
    <form
        @if (!empty($bulkShipBatch))
            action="{{ route('bulk_shipping.ship', $bulkShipBatch) }}"
        data-success="{{ route('bulk_shipping.batches') }}"
        data-bulk-ship-batch="true"
        data-in-progress-route="{{ route('bulk_shipping.inProgress') }}"
        data-removed-failed-batch-orders-route="{{ route('bulk_shipping.closeBulkShipBatch', $bulkShipBatch) }}"
        @else
            action="{{ route('packing.ship', ['order' => $order]) }}"
        data-success="{{ route('packing.index') }}"
        data-bulk-ship-batch="false"
        @endif
        method="POST"
        id="packing_form"
        class="h-lg-100"
    >
        @csrf
        @if (!empty($bulkShipBatch))
            <input type="hidden" name="bulk_ship_batch_id" value="{{ $bulkShipBatch->id }}" />
        @endif
        <input type="hidden" name="packing_state" id="packing_state" value="" />
        <input type="hidden" name="order_shipping_method_mappings" id="order-shipping-method-mappings" value="" />
        <input type="hidden" name="batch_filter[shipping_carrier]" id="batch-filter-shipping-carrier" value="" />
        <input type="hidden" name="batch_filter[shipping_method]" id="batch-filter-shipping-method" value="" />
        <input type="hidden" name="customer_id" value="{{ $order->customer_id }}" />
        <input type="hidden" name="total_unpacked_items" value="0" />
        <input type="hidden" name="total_unpacked_weight" value="0" />

        <div class="container-fluid py-4 h-lg-100">
            <div class="row h-lg-100">
                <div class="col-12 col-lg-6 h-lg-100 d-flex flex-column">
                    <div class="row packing-top-row">
                        <div class="col-12 d-flex align-items-center mb-4">
                            @if (empty($bulkShipBatch))
                                <a href="{{route('packing.index')}}" class="text-black font-md d-flex align-items-center">
                                    <i class="picon-arrow-backward-filled icon-lg icon-black mr-2"></i>
                                    {{ __('All Orders') }}
                                </a>
                            @else
                                <a href="{{route('bulk_shipping.index')}}" class="text-black font-md d-flex align-items-center">
                                    <i class="picon-arrow-backward-filled icon-lg icon-black mr-2"></i>
                                    {{ __('All Batch Orders') }}
                                </a>
                            @endif
                            <a class="text-black font-md d-flex align-items-center ml-3 show-last-shipments-button cursor-pointer">
                                <i class="picon-inbox-light icon-lg icon-black mr-2"></i>
                                {{ __('Last 10 Shipments') }}
                            </a>
                        </div>
                    </div>
                    <div class="card overflow-hidden mb-lg-0 h-lg-100">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-12 d-flex align-items-center">
                                    @if (!empty($bulkShipBatch))
                                        <h2 class="mb-0">{{ __('Bulk ship batch') }} {{ $bulkShipBatch->id }}</h2>
                                    @else
                                        <h2 class="mb-0">{{ __('Order') }} <a class="text-underline" target="_blank" href="{{ route('order.edit', ['order' => $order]) }}">{{ $order->number }}</a></h2>
                                    @endif
                                    @if (!empty($totes))
                                        <span class="badge bg-light px-3 py-2 font-xs ml-3">{{ __($totes) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="card-body pb-0 overflow-hidden d-flex flex-column">
                            @include('packing.orderDetails', [
                                'bulkShipBatchOrder' => $bulkShipBatchOrder ?? null,
                                'bulkShipBatch' => $bulkShipBatch,
                                'shippingBoxes' => $shippingBoxes
                            ])
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6 h-lg-100 d-flex flex-column">
                    <div class="row packing-top-row">
                        @if($order->packing_note)
                            <div class="col-12 d-flex align-items-center mb-4">
                                <div class="alert alert-warning font-xs m-0 py-2 d-flex align-items-center" role="alert">
                                    <i class="picon-alert-circled-light mr-1 text-white"></i><strong class="mr-1">{{ __('Packer Note:') }}</strong>{{ $order->packing_note }}
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="card overflow-hidden mb-0 h-lg-100 packing-card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h2 class="mb-0">{{ __('Shipment Details') }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pb-0 overflow-hidden d-flex flex-column">
                            @include('packing.shipmentDetails', [
                                'bulkShipBatchOrder' => $bulkShipBatchOrder ?? null,
                                'bulkShipBatch' => $bulkShipBatch,
                                'shippingBoxes' => $shippingBoxes
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('packing.lastShipments', ['shipments' => $shipments])
        @include('shared.modals.components.packing.customPackage')
        @include('shared.modals.components.packing.shippingRates')
        @include('shared.modals.components.packing.shippingAddress')
        @include('shared.modals.choosePrinter')
        @include('shared.modals.serialNumberAddModal')
        @include('shared.modals.packInQuantitesModal')
        @include('shared.modals.unpackInQuantitesModal')
        @include('shared.modals.components.bulk_shipping.shipping_information_edit')
        @include('shared.modals.components.bulk_shipping.filter_orders_in_batch_modal')
    </form>
@endsection

@push('js')
    <script>
        @if ($bulkShipBatch)
        new BulkShipOrders({{ $bulkShipBatch->id }})
        @endif

        new PackingSingleOrder(@json($order->id), @json($order->packing_note), @json($isWholesale));

        $(document).on('click', '.show-last-shipments-button', function(){
            $('.sidebar').addClass('active');
        })

        $(document).on('click', '.close-sidebar-button', function(){
            $('.sidebar').removeClass('active');
        })
    </script>
@endpush


