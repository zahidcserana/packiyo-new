@extends('layouts.app')

@section('content')
    @component('layouts.headers.auth', [
        'title' => 'Purchase Orders',
        'subtitle' => 'Receive Purchase Order'
    ])
    @endcomponent
    <div class="container-fluid">
        <div class="card">
            <div class="table-responsive p-0">
                <form id="purchase-order-receive-form" role="form" method="POST" action="{{ route('purchase_order.updatePurchaseOrder', compact('purchaseOrder')) }}">
                    @csrf
                    <table class="table align-items-center col-12 items-table" id="received-purchase-order-items-table">
                        <thead>
                            <tr class="text-center">
                                <td>{{__('Product')}}</td>
                                <td>{{__('Lot')}}</td>
                                <td>{{__('Location')}}</td>
                                <td>{{__('Ordered')}}</td>
                                <td>{{__('Received')}}</td>
                                <td>{{__('Rejected')}}</td>
                                <td>{{__('Accepted')}}</td>
                            </tr>
                        </thead>
                        <tbody style="cursor:pointer">
                            @foreach($purchaseOrder->purchaseOrderItems as $item)
                                <tr class="text-center">
                                    <td class="wrap">
                                        <div class="row align-items-center">
                                            <div class="col-sm-4">
                                                @if (empty($item->product->productImages[0]))
                                                    <img src="{{ asset('img/no-image.png') }}" alt="{{ __('No image') }}">
                                                @else
                                                    <img src="{{ $item->product->productImages[0]->source }}" width="30%">
                                                @endif
                                            </div>
                                            <div class="col-sm-8">
                                                <span>{{ $item->product->name }}</span><br>
                                                <span>SKU: {{ $item->product->sku }}</span>
                                                <a href="{{ route('product.barcode', ['product' => $item->product]) }}" target="_blank" class="table-icon-button">
                                                    <i class="picon-printer-light icon-lg align-middle"></i>
                                                </a>
                                            </div>
                                        </div>

                                    </td>
                                    <td>
                                        @if($item->product->lot_tracking == 1)
                                            <div class="searchSelect pt-5 mt-3" id="lot_id_container_{{ $item->id }}">
                                                @include('shared.forms.new.ajaxSelect', [
                                                   'url' => route('product.filterLots', ['product' => $item->product]),
                                                   'name' => 'lot_id[' . $item->id . ']',
                                                   'className' => 'ajax-user-input lot-id-container-' . $item->id,
                                                   'containerClass' => 'text-center',
                                                   'placeholder' => __('Search for a lot'),
                                                   'label' => '',
                                                   'default' => [
                                                       'id' => $item->product->lots->count() ? $item->product->lots->sortByDesc('created_at')->first()->id : '',
                                                       'text' => $item->product->lots->count() ? $item->product->lots->sortByDesc('created_at')->first()->name : ''
                                                   ]
                                               ])
                                            </div>
                                            <div id="lot_name_container_{{ $item->id }}">

                                            </div>
                                            <a href="#create-new-lot-modal" data-toggle="modal" data-id="{{ $item->id }}" data-product="{{ $item->product->id }}" data-customer="{{ $item->product->customer->id }}" data-lot="lot-id-container-{{ $item->id }}" class="btn btn-link" type="button">{{__('Create a new lot')}}</a>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="searchSelect received-po-location">
                                            @include('shared.forms.new.ajaxSelect', [
                                                'url' => route('purchase_order.filterLocations', ['warehouse' => $purchaseOrder->warehouse]),
                                                'name' => 'location_id[' . $item->id . ']',
                                                'className' => 'ajax-user-input',
                                                'containerClass' => 'mt-3',
                                                'placeholder' => __('Search for a location'),
                                                'label' => '',
                                                'default'=> (count($purchaseOrder->warehouse->locations) ? ['id'=>$purchaseOrder->warehouse->locations[0]['id'], 'text'=>$purchaseOrder->warehouse->locations[0]['name']] : [] )
                                            ])
                                        </div>
                                    </td>
                                    <td>
                                        {{ $item->quantity }}
                                    </td>
                                    <td>
                                        {{ $item->quantity_received }}
                                    </td>
                                    <td>
                                        <a href="#quantityRejectedModal" data-toggle="modal" data-id="{{ $item->id }}" class="btn btn-link">
                                            {{ $item->quantity_rejected }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="form-group text-left">
                                            <label for="quantity_received[{{ $item->id }}]"
                                                   data-id="quantity_received.{{ $item->id }}"
                                                   class="text-neutral-text-gray font-weight-600 font-xs">
                                            </label>
                                            <div
                                                class="input-group input-group-alternative input-group-merge tableSearch">
                                                <input
                                                    barcode="{{ Str::upper($item->product->barcode) }}"
                                                    barcodes="{{ $item->product->productBarcodes()->select(
                                                        DB::raw('UPPER(barcode) AS barcode'), 'quantity'
                                                    )->pluck('quantity', 'barcode') }}"
                                                    product="{{ $item->product->id }}"
                                                    class="product_receive form-control font-weight-600 h-auto p-2"
                                                    type="number"
                                                    name="quantity_received[{{ $item->id }}]"
                                                    value="0"
                                                >
                                                <input type="hidden" name="lot_tracking[{{ $item->id }}]" id="lot_tracking_{{ $item->id }}" value="{{(int)$item->product->lot_tracking}}"/>
                                                <input type="hidden" name="product_id[{{ $item->id }}]" id="product_id_{{ $item->id }}" value="{{ $item->product->id }}"/>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center align-items-center mb-4 mt-2">
                        <button type="submit"
                                class="btn bg-logoOrange text-white mx-auto px-5 font-weight-700 confirm-button"
                                id="submit-button">
                            {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @include('shared.modals.rejectedPurchaseOrderItemModal')
    @include('shared.modals.components.purchase_orders.create_new_lot')
@endsection

@push('js')
    <script>
        new PurchaseOrder;
    </script>
@endpush
