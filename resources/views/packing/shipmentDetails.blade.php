@php
    $predefinedShippingMethods = ['generic' => __('Generic')];
    $predefinedShippingMethods += \App\Models\ShippingMethodMapping::CHEAPEST_SHIPPING_METHODS;
@endphp
<div class="row">
    @if (empty($bulkShipBatch))
        <div class="col-12 col-sm-6 mb-2">
            <div class="row">
                <div class="col d-flex align-items-center mb-2">
                    <h3 class="m-0 opacity-3">{{ __('Shipping Address') }}</h3><span class="badge badge-secondary ml-2 d-none">{{ __('usps verified') }}</span>
                </div>
                <div class="w-100"></div>
                <div class="col cursor-pointer d-inline" data-target="#shippingInformationEdit" data-toggle="modal">
                    <span id="cont_info_name" class="font-xs">{{ $order->shippingContactInformation->name ?? '' }}</span>
                    <br>
                    <span id="cont_info_address" class="font-xs">{{ $order->shippingContactInformation->address ?? '' }}</span>
                    @if( !empty($order->shippingContactInformation->address2) )
                        <span id="cont_info_address2" class="font-xs">{{ $order->shippingContactInformation->address2 ?? '' }}</span>
                    @endif
                    <br>
                    <span id="cont_info_city" class="font-xs">{{ $order->shippingContactInformation->city ?? '' }}</span>
                    @if( !empty($order->shippingContactInformation->state) )
                        <span id="cont_info_state" class="font-xs">{{ $order->shippingContactInformation->state ?? '' }}</span>
                    @endif
                    <span id="cont_info_zip" class="font-xs">{{ $order->shippingContactInformation->zip ?? '' }}</span>
                    <span id="cont_info_country_name" class="font-xs">{{ $order->shippingContactInformation->country->name ?? '' }}</span>
                    <span id="cont_info_country_code" hidden>{{ $order->shippingContactInformation->country->iso_3166_2 ?? '' }}</span>
                </div>
            </div>
        </div>
    @endif
    @if (empty($bulkShipBatch))
        <div class="col-12 col-sm-6 mb-2">
            <div class="row">
                <div class="col d-flex align-items-center mb-2">
                    <h3 class="m-0 opacity-3">{{ __('Shipping Method') }} <i class="picon-alert-circled-light ml-1" data-toggle="tooltip" data-placement="top" data-html="true" title="{{ __('Method set on webshop: :method', ['method' => $order->shipping_method_name]) }}<br />{{ __('Service set on webshop: :service', ['service' => $order->shipping_method_code]) }}"></i></h3><span class="badge badge-secondary ml-2 d-none">{{ __('applied by co-pilot') }}</span>
                </div>
                <div class="w-100"></div>

                <input type="hidden" name="rate" id="rate" value="">
                <input type="hidden" name="rate_id" id="rate-id" value="">
                <div class="col">
                        @include('shared.forms.select', [
                           'name' => 'shipping_method_id',
                           'containerClass' => 'float-right w-100',
                           'label' => '',
                           'placeholder' => __('Shipping method'),
                           'error' => false,
                           'value' => $order->getMappedShippingMethodIdOrType(),
                           'options' => $predefinedShippingMethods + $shippingMethods->pluck('carrierNameAndName', 'id')->all()
                        ])
                </div>
                <div class="w-100"></div>
                <div class="col mt-2" id="drop-point-info" hidden>
                    <input type="hidden" name="drop_point_id" id="drop_point_id">
                    <b>{{ __('Drop point:') }}</b> <span id="drop-point-details"></span>
                </div>
                <div class="w-100"></div>
                <div class="col mt-2" id="drop-point-modal" hidden>
                    @foreach($shippingMethods->pluck('settings.has_drop_points', 'id') as $id => $dropPoint)
                        <input type="hidden" name="check-drop-point-{{ $id }}" id="check-drop-point-{{ $id }}" value="{{ $dropPoint }}">
                    @endforeach
                    <a
                        href="#select-drop-point-modal"
                        data-toggle="modal"
                        data-target="#select-drop-point-modal"
                        data-customer="{{ $order->customer_id }}"
                        id="select-drop-points-button"
                        class="btn btn-sm btn-icon bg-blue text-white m-0"
                    >
                        {{ __('Select Drop Point') }}
                    </a>
                    @include('shared.modals.components.selectDropPoint')
                </div>
            </div>
        </div>
    @endif
</div>

<div class="row my-2">
    <div class="col-lg-12">
        <ul class="nav nav-pills nav-fill" id="package_buttons_container">
            <li class="nav-item ml-auto">
                <button type="button" id="add_package" class="btn nav-link active {{ !empty($bulkShipBatch) ? 'd-none' : '' }}">+</button>
            </li>
        </ul>
    </div>
</div>

<div class="row pt-2 border-top">
    <div class="col-8 col-md-9 mb-2">
        <div class="row">
            <div class="col-12 col-xl-4 d-flex justify-content-center align-items-center">
                <label class="form-control-label text-neutral-text-gray font-weight-600 font-xs m-0 mr-2" data-id="length" for="input-length">{{ __('Packing:') }}</label>
                <select id="shipping_box" name="shipping_box" class="form-control form-control-sm" data-toggle="select" data-placeholder="{{ __('Choose shipping box') }}">
                    @foreach($shippingBoxes as $shippingBox)
                        <option
                            value="{{$shippingBox->id}}"
                            data-barcode="{{ Str::upper($shippingBox->barcode) }}"
                            data-default-name="{{ $shippingBox->name }}"
                            data-weight="{{ $shippingBox->weight }}"
                            data-length="{{ $shippingBox->length }}"
                            data-width="{{ $shippingBox->width }}"
                            data-height="{{ $shippingBox->height }}"
                            data-weight-locked="{{ $shippingBox->weight_locked }}"
                            data-height-locked="{{ $shippingBox->height_locked }}"
                            data-length-locked="{{ $shippingBox->length_locked }}"
                            data-width-locked="{{ $shippingBox->width_locked }}"
                            @if ($shippingBox->id == $order->shipping_box_id) selected="selected" @endif
                        >{{$shippingBox->name}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-xl-8 mt-2 mt-xl-0 pl-xl-0">
                <div class="row ml-0">
                    <div class="col-4 pl-0">
                        <div class="form-group d-flex justify-content-center align-items-center m-0">
                            <label class="form-control-label text-neutral-text-gray font-weight-600 font-xs m-0 mr-2" data-id="length" for="input-length">L({{ customer_settings($order->customer->id, \App\Models\CustomerSetting::CUSTOMER_SETTING_DIMENSIONS_UNIT) }}):</label>
                            <input autocomplete="" type="number" name="length" id="length" class="p-2 form-control form-control-sm font-sm" value="0" step="0.01">
                        </div>
                    </div>
                    <div class="col-4 pl-0">
                        <div class="form-group d-flex justify-content-center align-items-center m-0">
                            <label class="form-control-label text-neutral-text-gray font-weight-600 font-xs m-0 mr-2" data-id="length" for="input-length">W({{ customer_settings($order->customer->id, \App\Models\CustomerSetting::CUSTOMER_SETTING_DIMENSIONS_UNIT) }}):</label>
                            <input autocomplete="" type="number" name="width" id="width" class="p-2 form-control form-control-sm font-sm " value="0" step="0.01">
                        </div>
                    </div>
                    <div class="col-4 pl-0">
                        <div class="form-group d-flex justify-content-center align-items-center m-0">
                            <label class="form-control-label text-neutral-text-gray font-weight-600 font-xs m-0 mr-2" data-id="length" for="input-length">H({{ customer_settings($order->customer->id, \App\Models\CustomerSetting::CUSTOMER_SETTING_DIMENSIONS_UNIT) }}):</label>
                            <input autocomplete="" type="number" name="height" id="height" class="p-2 form-control form-control-sm font-sm " value="0" step="0.01">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4 col-md-3 mb-2 border-left">
        <div class="row h-100 d-flex align-items-center justify-content-center">
            <div class="col">
                <div class="row">
                    <div class="col-12 d-flex align-items-center justify-content-center flex-column flex-xl-row">
                        <label class="form-control-label text-neutral-text-gray font-weight-600 font-xs m-0 mr-2" data-id="length" for="input-length">{{ __('Weight') }}({{ customer_settings($order->customer->id, \App\Models\CustomerSetting::CUSTOMER_SETTING_WEIGHT_UNIT) }}):</label>
                        <input type="number" step="0.001" min="0.001" placeholder="0" id="weight" name="weight" class="w-100 text-center form-control form-control-sm font-md font-weight-600 border-0 text-body"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mx--4 overflow-auto h-lg-100">
    <div class="col-12 p-0 overflow-auto">
        <div id="package_container"></div>
    </div>
</div>

<div class="row mx--4 mt-2 py-3 border-top packing-buttons-row">
    <div class="col-12 col-sm-7 d-flex align-items-center notices">
        @if(count($order->notReadyToShipExplanation()) > 0)
            <span class="font-xs m-0 pb-2 pb-sm-0 d-flex align-items-center"><i class="picon-alert-circled-light mr-1"></i>{{ __('This order is on hold and cannot be shipped.') }}</span>
        @elseif($order->allow_partial)
        @else
            <span class="font-xs m-0 pb-2 pb-sm-0 d-flex align-items-center"><i class="picon-alert-circled-light mr-1"></i>{{ __('All items must be packed on this order before you can ship.') }}</span>
        @endif
    </div>
    <div class="col-12 col-sm-5 d-flex justify-content-end align-items-center" id="submit_print_container">
        @if (!$bulkShipBatch)
            <div class="d-flex align-items-center">
                <div data-target="#choosePrinter" data-toggle="modal" class="mr-2">
                    <i  class="pr-2 picon-printer-light icon-lg align-middle"></i>
                </div>
                <a href="{{route('order.getOrderSlip', ['order'=>$order])}}" target="_blank" id="order_slip_submit" class=" font-weight-700 d-inline-block d-md-none">
                    <i class="picon-receipt-light icon-lg rounded icon-background text-white d-block d-md-none"></i>
                </a>
            </div>
        @else
            <input type="hidden" name="printer_id" value="pdf" id="input-printer_id" />
        @endif
        @if ($bulkShipBatch)
            <div class="d-inline-flex flex-row-reverse">
                <input
                    type="number"
                    name="batch_shipping_limit"
                    class="form-control w-50 mr-2"
                    placeholder="{{ __('Shipping limit') }}"
                    value="{{
                        min(
                            $bulkShipBatch->orders()->wherePivotNull('shipment_id')->count() !== 0
                                ? $bulkShipBatch->orders()->wherePivotNull('shipment_id')->count()
                                : $bulkShipBatch->orders->count(),
                            config('bulk_ship.batch_shipping_limit')
                        )
                    }}"
                >
            </div>
        @endif
        <input type="hidden" name="print_packing_slip"/>
        <div class="btn-group mr-1 confirm-button-group {{ (count($order->notReadyToShipExplanation()) > 0) ? 'on-hold' : '' }} {{ ($order->allow_partial) ? 'allow-partial' : '' }} {{ $bulkShipBatch && $bulkShipBatch->shipped ? 'd-none' : '' }}">
            <button disabled class="btn btn-light confirm-ship-button" type="button" id="confirm-dropdown">
                {{ __('Ship Order') }}
            </button>
            <button disabled type="button" class="btn btn-light dropdown-toggle dropdown-toggle-split opacity-8" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="sr-only">{{ __('Toggle Dropdown') }}</span>
            </button>
            <div class="dropdown-menu">
                <button type="button" data-dismiss="modal" class="dropdown-item ship-button">
                    {{ __('Ship Order') }}
                </button>
                <button type="button" data-dismiss="modal" class="dropdown-item ship-and-print-button">
                    {{ __('Ship and Print Order') }}
                </button>
            </div>
        </div>
        @if ($isWholesale)
            <button class="btn btn-light ml-2 packing-labels-button" disabled>
                {{ __('Packing Labels') }}
            </button>
            <button class="btn btn-blue done-with-packing-labels-button d-none">
                {{ __('Done') }}
            </button>
        @endif
    </div>
</div>
