@if (!empty($bulkShipBatch))
    <div class="row mx--4 mt--4 mb-4 overflow-auto h-50 border-bottom">
        <div class="col-12 p-0">
            @php
                $bulkShipBatchProgress = app('packing')->bulkShipBatchProgress($bulkShipBatch, 0)
            @endphp
            <p class="px-4">
                {{ __('Total orders in the batch:') }} <strong id="bulk-ship-orders-count">{{ Arr::get($bulkShipBatchProgress, 'statistics.total', 0) }}</strong><br />
                {{ __('Shipped orders:') }} <strong id="bulk-ship-orders-shipped-count">{{ Arr::get($bulkShipBatchProgress, 'statistics.total_shipped', 0) }}</strong><br />
                {{ __('Failed orders:') }} <strong id="bulk-ship-orders-failed-count">{{ Arr::get($bulkShipBatchProgress, 'statistics.failed', 0) }}</strong><br />
                {{ __('Remaining orders:') }} <strong id="bulk-ship-orders-remaining-count">{{ Arr::get($bulkShipBatchProgress, 'statistics.remaining', 0) }}</strong><br />
            </p>

            <table class="table" id="bulk-ship-orders">
                <thead>
                <tr>
                    <th class="border-top-0">{{ __('Order Number') }}</th>
                    <th class="border-top-0">{{ __('Shipping method') }}</th>
                    <th class="border-top-0">{{ __('Status') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($bulkShipBatch->orders as $bulkShipBatchOrder)
                    @php
                        $predefinedShippingMethods = ['generic' => __('Generic')];
                        $predefinedShippingMethods += \App\Models\ShippingMethodMapping::CHEAPEST_SHIPPING_METHODS;
                    @endphp
                    <tr data-id="{{ $bulkShipBatchOrder->id }}">
                        <td class="wrap align-middle">
                            <a class="font-xs font-weight-500" href="{{ route('order.edit', ['order' => $bulkShipBatchOrder]) }}" target="_blank">
                                {{ $bulkShipBatchOrder->number }}
                            </a>
                        </td>
                        <td class="wrap align-middle">
                            @include('shared.forms.select', [
                               'name' => "shipping_method_id[{$bulkShipBatchOrder->id}]",
                               'containerClass' => 'float-right w-100',
                               'label' => '',
                               'placeholder' => __('Shipping method'),
                               'error' => false,
                               'value' => $bulkShipBatchOrder->pivot->shipment_id ? \App\Models\Shipment::find($bulkShipBatchOrder->pivot->shipment_id)->shipping_method_id ?? '' : $bulkShipBatchOrder->getMappedShippingMethodIdOrType(),
                               'options' => $predefinedShippingMethods  + $shippingMethods->pluck('carrierNameAndName', 'id')->all(),
                               'native' => true
                            ])
                        </td>
                        <td class="bulk-ship-order-status wrap align-middle">
                            <span class="font-xs font-weight-500">
                                @if ($bulkShipBatchOrder->pivot->shipped || $bulkShipBatchOrder->pivot->shipment_id)
                                    {{ __('Shipped') }}
                                @elseif ($bulkShipBatchOrder->pivot->errors)
                                    {{ __('Failed') }}
                                @else
                                    {{ __('Not shipped') }}
                                @endif
                            </span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
<div class="row mb-4">
    <div class="col-6">
        <strong id="items-in-order">0 {{ __('Items in Order') }}</strong>
    </div>
    <div class="col-6 text-right">
        <strong id="items-remain">0 {{__('Items Remain')}}</strong>
    </div>
</div>
<div class="row h-100 mx--4 overflow-auto">
    <div class="col-12 p-0">
            <table id="items_listing" class="table package-items-table unpacked-items-table">
                <thead>
                <tr>
                    <th class="col-6">{{__('Item')}}</th>
                    <th class="col-3">{{__('Location')}}</th>
                    <th class="col-1">{{__('Quantity')}}</th>
                    <th class="col-2"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($order->orderItems as $key => $orderItem)
                        <?php if($orderItem->order_item_kit_id) continue; ?>
                        <?php if($orderItem->cancelled_at || $orderItem->quantity < 1) continue; ?>
                        <?php $orderItemQuantityToPack = isset($bulkShipBatch) || \Laravel\Pennant\Feature::for('instance')->inactive(\App\Features\RequiredReadyToPickForPacking::class) ? $orderItem->quantity_allocated : $orderItem->quantity_allocated_pickable; ?>
                    @if($orderItemQuantityToPack > 0 || ($orderItem->kitOrderItems->count() && $orderItem->kitOrderItems()->sum('quantity_allocated_pickable') > 0))
                        @if(isset($toteOrderItemArr[$orderItem->id]['locations']))
                            @foreach($toteOrderItemArr[$orderItem->id]['locations'] as $locId => $data)
                                @include('packing.orderItemRow', [
                                    'key' => $data['key'],
                                    'orderItem' => $data['order_item'],
                                    'toteOrderItem' => $data['tote_order_item'],
                                    'toteOrderItemLocationId' => $data['tote_order_item']->location->id,
                                    'toteOrderItemLocationName' => $data['tote_order_item']->location->name,
                                    'toteOrderItemToteId' => $data['tote_id'],
                                    'toteOrderItemToteName' => $data['tote_name'],
                                    'quantityToPickFromRow' => $data['tote_order_item_quantity'] <= $orderItemQuantityToPack ? $data['tote_order_item_quantity'] : $orderItemQuantityToPack,
                                    'bulkShipBatch' => $bulkShipBatch ?? null
                                ])
                            @endforeach
                        @endif

                        @if($toteOrderItemArr[$orderItem->id]['total_picked'] < $orderItemQuantityToPack || $orderItem->kitOrderItems->count())
                            @include('packing.orderItemRow', [
                                'key' => $key,
                                'orderItem' => $orderItem,
                                'toteOrderItem' => null,
                                'toteOrderItemLocationId' => 0,
                                'toteOrderItemLocationName' => null,
                                'toteOrderItemToteId' => 0,
                                'toteOrderItemToteName' => null,
                                'quantityToPickFromRow' => $orderItem->kitOrderItems->count() ? $orderItem->quantity : $orderItemQuantityToPack - $toteOrderItemArr[$orderItem->id]['total_in_totes']
                            ])

                            @if($orderItem->kitOrderItems->count())
                                <input type="hidden" id="to-pack-per-kit-{{$orderItem->id}}" value="{{ $orderItem->kitOrderItems->sum('quantity') / $orderItem->quantity }}" />
                            @endif
                        @endif

                        @foreach($orderItem->kitOrderItems as $kitOrderItemKey => $kitOrderItem)
                                <?php $kitOrderItemQuantityToPack = isset($bulkShipBatch) || \Laravel\Pennant\Feature::for('instance')->inactive(\App\Features\RequiredReadyToPickForPacking::class) ? $kitOrderItem->quantity_allocated : $kitOrderItem->quantity_allocated_pickable; ?>
                            @if(isset($toteOrderItemArr[$kitOrderItem->id]['locations']))
                                @foreach($toteOrderItemArr[$kitOrderItem->id]['locations'] as $locId => $data)
                                    @include('packing.orderItemRow', [
                                        'key' => $data['key'],
                                        'orderItem' => $data['order_item'],
                                        'toteOrderItem' => $data['tote_order_item'],
                                        'toteOrderItemLocationId' => $data['tote_order_item']->location->id,
                                        'toteOrderItemLocationName' => $data['tote_order_item']->location->name,
                                        'toteOrderItemToteId' => $data['tote_id'],
                                        'toteOrderItemToteName' => $data['tote_name'],
                                        'quantityToPickFromRow' => $data['tote_order_item_quantity'] <= $kitOrderItemQuantityToPack ? $data['tote_order_item_quantity'] : $kitOrderItemQuantityToPack,
                                    ])
                                @endforeach
                            @endif

                            @if($toteOrderItemArr[$kitOrderItem->id]['total_picked'] < $kitOrderItemQuantityToPack)
                                @include('packing.orderItemRow', [
                                    'key' => $key . '_' . $kitOrderItemKey,
                                    'orderItem' => $kitOrderItem,
                                    'toteOrderItem' => null,
                                    'toteOrderItemLocationId' => 0,
                                    'toteOrderItemLocationName' => null,
                                    'toteOrderItemToteId' => 0,
                                    'toteOrderItemToteName' => null,
                                    'quantityToPickFromRow' => $kitOrderItemQuantityToPack - $toteOrderItemArr[$kitOrderItem->id]['total_in_totes']
                                ])
                            @endif

                            <input type="hidden" class="to-pack-total" id="to-pack-total-{{$kitOrderItem->id}}" value="{{$kitOrderItemQuantityToPack}}" />
                            <input type="hidden" id="packed-total-{{$kitOrderItem->id}}" value="0" />
                        @endforeach
                    @endif

                    <input type="hidden" class="to-pack-total @if($orderItem->kitOrderItems->count()) to-pack-total-skip-calculation @endif" id="to-pack-total-{{$orderItem->id}}" value="{{$orderItem->kitOrderItems->count() ? $orderItem->quantity_pending : $orderItemQuantityToPack}}" />
                    <input type="hidden" id="packed-total-{{$orderItem->id}}" value="0" />
                @endforeach
                </tbody>
            </table>
    </div>
</div>
