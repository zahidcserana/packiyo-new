<form action="{{ route('order.return', ['order' => $order->id]) }}" class="return-order-form" data-type="PUT" enctype="multipart/form-data">
    @csrf
    {{ method_field('PUT') }}
    <input type="hidden" value="{{ $order->id }}" name="order_id">
    <input type="hidden" value="{{ $order->customer->id }}" name="customer_id">
    <input type="hidden" value="{{ $defaultWarehouse['id'] ?? '' }} " name="warehouse_id">
    <input type="hidden" value="{{ $defaultReturnStatus['id'] ?? '' }}" name="return_status_id">
    @if (app('printer')->getDefaultLabelPrinter($order->customer))
        <input type="hidden" name="printer_id" value="{{ app('printer')->getDefaultLabelPrinter($order->customer)->id }}" id="input-printer_id">
    @else
        <input type="hidden" name="printer_id" value="" id="input-printer_id" />
    @endif
    <input type="hidden" value="1" name="step">
    <div class="row w-100">
        <div class="d-none col-12 col-lg-12 mb-4 complete-step">
            <div class="row">
                <div class="col-12 col-lg-7">
                    <div class="flex-column">
                        <div class="text-right text-lg-left">
                            {{__('Ship to')}}&nbsp;
                            <i class="picon-edit-filled icon-lg icon-orange" data-target="#shippingInformationEdit" data-toggle="modal"></i>
                        </div>
                        <div class="row text-word-break">
                            <div class="col-lg-5 col-12">
                                <div class="text-right text-lg-left">
                                    <strong><span id="cont_info_name">{{ $order->shippingContactInformation->name ?? '' }}</span></strong>
                                </div>
                                <div class="d-none d-lg-block">
                                    <span id="cont_info_email">{{ $order->shippingContactInformation->email ?? '' }}</span>
                                    <br>
                                    <span id="cont_info_phone">{{ $order->shippingContactInformation->phone ?? '' }}</span>
                                </div>
                            </div>
                            <div class="col-12 col-lg-7 d-none d-lg-block">
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
                                    <span id="cont_info_country_code" hidden>{{ $order->shippingContactInformation->country->iso_3166_2 ?? '' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="text-left">
                        <div id="shipping_method_container" class="row text-left align-items-center">
                            <div class="col-12 col-md-12 my-1 mt-3">
                                {{ __('Return shipping method') }}
                            </div>
                            <div class="col-12 col-md-12 my-1">
                                <div class="searchSelect">
                                    @include('shared.forms.select', [
                                        'name' => 'shipping_method_id',
                                        'containerClass' => 'float-right w-100',
                                        'label' => '',
                                        'class' => '',
                                        'idPrefix' => 'return',
                                        'placeholder' => __('Shipping method'),
                                        'error' => false,
                                        'value' => $returnShippingMethod->id ?? '',
                                        'options' => ['generic' => __('Generic')] + $shippingMethods->filter(fn($shippingMethod) => empty($shippingMethod->settings['has_drop_points']))->pluck('carrierNameAndName', 'id')->all()
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-12 content-step">
            <div class="return-items-table table-responsive mb-4 px-0 items-table overflow-x-auto">
                <table class="table align-items-center table-flush">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Image') }}</th>
                            <th scope="col">{{ __('Product') }}</th>
                            <th scope="col" class="text-center">{{ __('Shipped') }}</th>
                            <th scope="col" class="text-center">{{ __('Returned') }}</th>
                        </tr>
                    </thead>
                    <tbody id="item_container">
                        @foreach( $shippedOrderItems as $key => $result)
                        <tr class="productRow parentProductRow" data-index="{{ $result['order_item_id'] }}">
                            <td>
                                <img src="{{ $result['image'] }}" alt="">
                            </td>
                            <td>{!! $result['text'] !!}</td>
                            <td class="text-center">{{ $result['quantity'] }}</td>
                            <td class="text-center">
                                <div class="returned-input input-group input-group-alternative input-group-merge bg-lightGrey font-sm">
                                    <input
                                        class="form-control font-sm bg-lightGrey font-weight-600 text-black h-auto p-2 order-item-quantity"
                                        name="order_items[{{ $result['order_item_id'] }}][quantity]"
                                        type="number"
                                        value="0"
                                        max="{{ $result['quantity'] }}"
                                        min="0"
                                    >
                                    <input name="order_items[{{ $result['order_item_id'] }}][is_returned]" type="hidden" value="1" checked="checked">
                                </div>
                                <input type="hidden" name="order_items[{{ $result['order_item_id'] }}][product_id]" value="{{ $result['id'] }}">
                                <input type="hidden" name="order_items[{{ $result['order_item_id'] }}][tote_id]" value="">
                                <input type="hidden" name="order_items[{{ $result['order_item_id'] }}][location_id]" value="{{ $result['location_id'] }}">
                                <input type="hidden" name="order_items[{{ $result['order_item_id'] }}][order_item_id]" value="{{ $result['order_item_id'] }}">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-none form-group mb-0 mx-2 text-left mt-5 mb-3 reason-editor">
                <label for="reason"
                       class="text-neutral-text-gray font-weight-600 font-xs">{{ __('Reason:') }}</label>
                <textarea id="reason"
                          name="reason"
                          class="px-2 text-black form-control"
                ></textarea>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 col-lg-12">
            <div class="d-flex justify-content-end px-2 action-section">
                <div class="custom-form-checkbox d-none own-label-check">
                    @include('shared.forms.checkbox', [
                        'name' => 'own_label',
                        'containerClass' => '',
                        'label' => ('Customer is providing their own label'),
                        'checked' => false,
                        'value' => true
                    ])
                </div>
                <button type="button" class="d-none btn bg-logoOrange text-white px-5 py-2 mr-4 font-weight-700 border-8 next-link">
                    {{ __('Next') }}
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    new OrderReturnForm();
</script>
