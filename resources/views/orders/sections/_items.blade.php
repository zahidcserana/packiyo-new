<div class="searchSelect">
    @if(!$isLockedForEditing)
        @include('shared.forms.ajaxSelect', [
            'url' => route('order.filterProducts', ['customer' => $order->customer]),
            'name' => 'name',
            'className' => 'ajax-user-input order-item-input',
            'placeholder' => __('Add product'),
            'labelClass' => 'd-block',
            'label' => ''
        ])
    @endif
</div>
<div class="table-responsive px-md-2 px-0 has-scrollbar items-table searchedProducts">
    @if(! empty($errors->all()) && count($errors->all()))
        @foreach($errors->getMessages() as $key => $error)
            @if(explode('.', $key)[0] === 'order_items')
                <span class="text-danger form-error-messages font-weight-600 font-xs">{{ $error[0] }}</span>
            @endif
        @endforeach
    @endif
    <table class="col-12 table align-items-center table-small-paddings table-th-small-font table-td-small-font table-flush">
        <thead>
        <tr>
            <th scope="col">{{ __('Image') }}</th>
            <th scope="col">{{ __('Product') }}</th>
            <th scope="col">{{ __('Unit Price') }}</th>
            <th scope="col">{{ __('Quantity') }}</th>
            <th scope="col">{{ __('Pending') }}</th>
            <th scope="col">{{ __('Allocated') }}</th>
            <th scope="col">{{ __('Shipped') }}</th>
            <th scope="col">{{ __('Backordered') }}</th>
            <th scope="col">{{ __('Returned') }}</th>
            <th scope="col">{{ __('Total Price') }}</th>
            <th scope="col">&nbsp;</th>
        </tr>
        </thead>
        <tbody id="item_container">
        @foreach( $order->orderItems as $key => $orderItem )
            @if(is_null($orderItem->order_item_kit_id))
                <tr class="productRow parentProductRow" data-index="{{ $key }}">
                    <td class="image-value">
                        @if ($orderItem->product && isset($orderItem->product->productImages->first()->source))
                            <img src="{{ $orderItem->product->productImages->first()->source }}" alt="">
                            <input type="hidden" name="order_items[{{ $key }}][img]" value="{{ $orderItem->product->productImages->first()->source }}">
                        @else
                            <img src="{{ asset('img/no-image.png') }}" alt="No image">
                        @endif
                    </td>
                    <td class="product-text">
                        <input type="hidden" name="order_items[{{ $key }}][is_kit_item]" value="false">
                        {{ __('SKU:') }} {!! $orderItem['sku'] !!} <br>
                        @if ($orderItem->product)
                            {{ __('Name:') }} <a href="{{ route('product.edit', $orderItem['product']) }}" target="_blank">{!! $orderItem['name'] !!}</a>
                        @else
                            <input type="hidden" name="order_items[{{ $key }}][sku]" value="{{ $orderItem->sku }}">
                            <span class="text-red">
                                {{ __('Not imported from channel') }}
                            </span>
                        @endif
                        @if(!is_null($orderItem['cancelled_at']))
                            <br>
                            <span class="text-red">
                                {{ __('Cancelled') }}
                            </span>
                        @endif
                        <input type="hidden" name="order_items[{{ $key }}][text]" value="SKU: {!! $orderItem['sku'] !!} <br> Name: {!! $orderItem['name'] !!}">
                    </td>
                    <td class="product-price">
                        <span class="price-value">{{ $orderItem['price'] }}</span>
                        <input type="hidden" name="order_items[{{ $key }}][price]" value="{{ $orderItem['price'] }}">
                    </td>
                    <td>
                        <div class="input-group input-group-alternative input-group-merge font-sm number-input">
                            <input type="number"
                                   class="quantity-input form-control font-weight-600 px-2 py-1"
                                   name="order_items[{{ $key }}][quantity]"
                                   value="{{ $orderItem['quantity'] }}"
                                   data-index="{{ $key }}"
                                   data-child-count="{{ count($orderItem->kitOrderItems) }}"
                                   step="1"
                                   min="0"
                                   @if($orderItem['quantity'] < 1 || $orderItem['cancelled_at'] || $isLockedForEditing) readonly @endif
                            />
                        </div>
                    </td>
                    @if($orderItem->product && $orderItem->product['type'] == \App\Models\Product::PRODUCT_TYPE_VIRTUAL)
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                    @else
                        <td>
                            {{ $orderItem['quantity_pending'] }}
                        </td>
                        <td>
                            <span data-toggle="tooltip" data-placement="top" data-html="true" title="{{ __('Allocated from pickable locations: :quantity', ['quantity' => $orderItem['quantity_allocated_pickable'] ?? 0]) }}">{{  $orderItem['quantity_allocated'] }}</span>
                        </td>
                        <td>
                            {{ $orderItem['quantity_shipped'] }}
                        </td>
                        <td>
                            {{ $orderItem['quantity_backordered'] }}
                        </td>
                        <td>
                            {{ $orderItem['quantity_returned'] }}
                        </td>
                    @endif
                    <td class="item-total-price">
                    </td>
                    <td class="delete-row productList">
                        <input type="hidden" name="order_items[{{ $key }}][product_id]" value="{{ $orderItem['product_id'] }}"/>
                        <input type="hidden" name="order_items[{{ $key }}][order_item_id]" value="{{ $orderItem['id'] }}"/>
                        @if(!$isLockedForEditing)
                            @if(is_null($orderItem['cancelled_at']))
                                <button
                                    data-cancel-item-action="{{ route('orderItem.cancel', ['order' => $order->id, 'orderItem' => $orderItem['id']]) }}"
                                    data-order-sku-number="{!! $orderItem['sku'] !!}"
                                    data-order-number="{!! $order->number ?? old('order')['number'] !!}"
                                    data-product-name="{!! $orderItem['name'] !!}"
                                    class="cancelOrderItem"
                                    type="button"
                                    title="{{ __('Cancel order item') }}">
                                    <i class="picon-trash-filled" title="{{ __('Cancel') }}"></i>
                                </button>
                            @else
                                <button
                                    data-uncancel-item-action="{{ route('orderItem.uncancel', ['order' => $order->id, 'orderItem' => $orderItem['id']]) }}"
                                    data-order-sku-number="{!! $orderItem['sku'] !!}"
                                    data-order-number="{!! $order->number ?? old('order')['number'] !!}"
                                    data-product-name="{!! $orderItem['name'] !!}"
                                    class="uncancel-order-item"
                                    type="button"
                                    title="{{ __('Uncancel order item') }}">
                                    <i class="picon-reload-filled" title="{{ __('Uncancel') }}"></i>
                                </button>
                            @endif
                        @endif
                    </td>
                    @if($orderItem->product && $orderItem->product['type'] == \App\Models\Product::PRODUCT_TYPE_DYNAMIC_KIT)
                        <td>
                            <div>
                                <a href="#" class="btn bg-logoOrange text-white mx-auto px-3 font-weight-700 confirm-button modal-submit-button" data-toggle="modal" data-target="#showDynamicKitItems" data-id="{{ $orderItem['id'] }}">
                                    {{ __('Show components') }}
                                </a>
                            </div>
                        </td>
                    @endif
                </tr>
                @if($orderItem->product && $orderItem->product->kitItems && $orderItem->kitOrderItems && count($orderItem->kitOrderItems) > 0)
                    @foreach($orderItem->kitOrderItems as $k => $kitItem)
                        <tr class="order-item-fields productRow" data-index="{{ $key + $k + 1 }}" id="{{$kitItem->id}}_child_products">
                            <input type="hidden" name="order_items[{{ $key + $k + 1 }}][order_item_id]" value="{{ $kitItem['id'] }}"/>
                            <input type="hidden" name="order_items[{{ $key + $k + 1 }}][parent_product_id]" value="{{ $orderItem['id'] }}"/>
                            <td class="pl-2">
                                @if (isset($kitItem->product->productImages->first()->source))
                                    <img src="{{ $kitItem->product->productImages->first()->source }}" alt="">
                                    <input type="hidden" name="kit_items[{{ $key }}][img]" value="{{ $kitItem->product->productImages->first()->source }}">
                                @else
                                    <img src="{{ asset('img/no-image.png') }}" alt="{{ __('No image') }}">
                                @endif
                            </td>
                            <td class="product-text">
                                <input type="hidden" name="order_items[{{ $key + $k + 1 }}][is_kit_item]" value="true">
                                {{'SKU: ' . $kitItem->sku }}  <br> {{__('Name')}}: <a href="{{ route('product.edit', $kitItem['product']) }}" target="_blank">{{$kitItem->name}}</a> <br>
                                @if(($orderItem['cancelled'] ?? []) == 1 || $kitItem['quantity'] == 0)
                                    <span>{{ __('Status: ') }}</span><span style="color: red" class="kitStatus kit-status-{{ $orderItem['product_id'] }}">{{ __('Cancelled') }}</span>
                                @elseif(($orderItem['cancelled'] ?? []) == 0)
                                    <span>{{ __('Status: ') }}</span><span class="kitStatus kit-status-{{ $kitItem['product_id'] }}">{{ __('Pending') }}</span>
                                @endif
                                <input type="hidden" name="order_items[{{ $key + $k + 1 }}][text]" value="{{ $orderItem['text'] }}">
                                <input type="hidden" class="order-item-{{ $kitItem['product_id'] }}" value="{{ $orderItem['cancelled'] ?? '' }}" name="order_items[{{ $key + $k + 1 }}][cancelled]">

                            </td>
                            <td>{{$kitItem->price}}</td>
                            <td>
                                <div class="input-group input-group-alternative input-group-merge font-sm number-input">
                                    <input data-quantity="{{ $orderItem->product->kitItems->firstWhere('pivot.child_product_id', $kitItem->product_id)->pivot->quantity ?? 1 }}"
                                           type="number" readonly class="quantity-input form-control font-weight-600 px-2 py-1 childquantity-input_{{ $key + $k + 1 }}"
                                           value="{{ $kitItem['quantity'] }}"
                                           name="order_items[{{ $key + $k + 1 }}][quantity]"
                                    />
                                </div>
                            </td>
                            <td>{{$kitItem->quantity_pending}}</td>
                            <td>
                                <span data-toggle="tooltip" data-placement="top" data-html="true" title="{{ __('Allocated from pickable locations: :quantity', ['quantity' => $kitItem['quantity_allocated_pickable'] ?? 0]) }}">{{ $kitItem['quantity_allocated'] }}</span>
                            </td>
                            <td>{{$kitItem->quantity_shipped}}</td>
                            <td>{{$kitItem->quantity_backordered}}</td>
                            <td>{{$kitItem->quantity_returned}}</td>
                            <td class="item-total-price">

                            </td>
                            @if(!$isLockedForEditing)
                                @if(!is_null($kitItem['cancelled_at']))
                                    <td class="productList d-flex align-items-center p-3">
                                        <input type="hidden" name="order_items[{{ $key }}][product_id]" value="{{ $kitItem['product_id'] }}"/>
                                        <a href="#">
                                            <button
                                                data-uncancel-item-action="{{ route('orderItem.uncancel', ['order' => $order->id, 'orderItem' => $kitItem->id]) }}"
                                                data-order-sku-number="{!! $kitItem['sku'] !!}"
                                                data-order-number="{!! $order->number ?? old('order')['number'] !!}"
                                                data-product-name="{!! $kitItem->name !!}"
                                                class="uncancel-order-item"
                                                type="button"
                                                title="{{ __('Uncancel kit item') }}">
                                                <i class="picon-reload-light" title="{{ __('Uncancel kit item') }}"></i>
                                            </button>
                                        </a>
                                    </td>
                                @else
                                    <td class="productList d-flex align-items-center p-3">
                                        <input type="hidden" name="order_items[{{ $key + $k + 1 }}][product_id]" value="{{ $kitItem['product_id'] }}"/>
                                        <a href="#">
                                            <button
                                                data-cancel-item-action="{{ route('orderItem.cancel', ['order' => $order->id, 'orderItem' => $kitItem->id]) }}"
                                                data-order-sku-number="{!! $kitItem['sku'] !!}"
                                                data-order-number="{!! $order->number ?? old('order')['number'] !!}"
                                                data-product-name="{!! $kitItem->name !!}"
                                                class="cancel-order-item"
                                                type="button"
                                                title="{{ __('Cancel kit item') }}">
                                                <i class="picon-trash-light" title="{{ __('Cancel kit item') }}"></i>
                                            </button>
                                        </a>
                                    </td>
                                @endif
                            @endif
                            <input type="hidden" name="order_items[{{$key + $k + 1}}][product_id]" value="{{ $kitItem['product_id'] }}">
                        </tr>
                    @endforeach
                @endif
            @endif
        @endforeach
        </tbody>
    </table>
</div>
<div class="row mt-4 mx-0">
    <div class="col-12 col-lg-8 px-xl-2">
        <div class="row font-sm mx-0">
            <div class="col-lg-6 col-12 text-word-break">
                <div class="text-lg-left mb-2">
                    {{__('Ship to')}}
                    <i class="picon-edit-filled icon-lg icon-orange" data-target="#order-shipping-information-edit" data-toggle="modal"></i>
                </div>
                <div class="text-lg-left">
                    <strong><span id="cont_info_name">{{ $order->shippingContactInformation->name ?? '' }}</span></strong>
                </div>
                <div class="d-none d-lg-block">
                    <span id="cont_info_email">{{ $order->shippingContactInformation->email ?? '' }}</span>
                    <br>
                    <span id="cont_info_phone">{{ $order->shippingContactInformation->phone ?? '' }}</span>
                </div>
                <div class="text-lg-left mb-2">
                    {{__('Bill to')}}
                    <i class="picon-edit-filled icon-lg icon-orange" data-target="#orderBillingInformationEdit" data-toggle="modal"></i>
                </div>
            </div>
            <div class="col-6 col-lg-6 d-none d-lg-block text-word-break">
                <div>
                    @if( !empty($order->shippingContactInformation->company_name) || !empty($order->shippingContactInformation->company_number) )
                        <span id="cont_info_company_name">{{ $order->shippingContactInformation->company_name ?? '' }}</span>&nbsp;
                        <span id="cont_info_company_number">{{ $order->shippingContactInformation->company_number ?? '' }}</span>
                        <br>
                    @endif
                    <span id="cont_info_address">{{ $order->shippingContactInformation->address ?? '' }}</span>
                    <br>
                    @if( !empty($order->shippingContactInformation->address2) )
                        <span id="cont_info_address2">{{ $order->shippingContactInformation->address2 ?? '' }}</span>
                        <br>
                    @endif
                    <span id="cont_info_city">{{ $order->shippingContactInformation->city ?? '' }}</span>
                    <span id="cont_info_state">{{ $order->shippingContactInformation->state ?? '' }}</span>
                    <span id="cont_info_zip">{{ $order->shippingContactInformation->zip ?? '' }}</span>
                    <br>
                    <span id="cont_info_country_name">{{ $order->shippingContactInformation->country->name ?? '' }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4 total-table">
        <div class="row font-sm mx-0">
            <div class="col-6">{{ __('Subtotal:') }}</div>
            <div class="col-6 subtotal-value"></div>
        </div>
        <div class="row font-sm mx-0">
            <div class="col-6">{{ __('Shipping:') }}</div>
            <div class="col-6 total-shipping">{{ $order->shipping ? number_format($order->shipping, 2, '.', '') : 0.00 }}</div>
        </div>
        <div class="row font-sm mx-0">
            <div class="col-6">{{ __('Discount:') }}</div>
            <div class="col-6 total-discount">{{ $order->discount ? number_format($order->discount, 2, '.', '') : 0.00 }}</div>
        </div>
        <div class="row font-sm mx-0">
            <div class="col-6">{{ __('Taxes:') }}</div>
            <div class="col-6 total-taxes">{{ $order->tax ? number_format($order->tax, 2, '.', '') : 0.00 }}</div>
        </div>
        <div class="row font-sm mx-0 font-weight-bold">
            <div class="col-6">{{ __('Total:') }}</div>
            <div class="col-6 total-value"></div>
        </div>
    </div>
    <div class="col-12 col-lg-12 d-flex justify-content-end mt-4 px-2">
        <button class="save-changes btn bg-logoOrange text-white my-2 px-3 py-2 font-weight-700 border-8">{{ __('Save') }}</button>
    </div>
</div>
@include('shared.modals.orderShippingInformationEditModal', ['order' => $order, 'buttonClass' => 'float-right'])
@include('shared.modals.orderBillingInformationEditModal', ['order' => $order, 'buttonClass' => 'float-right'])
