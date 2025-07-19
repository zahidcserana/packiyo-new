<div class="modal-header border-bottom">
    <h6 class="modal-title">{{ __('Calculated Shipping Rates') }}<br><span class="font-xs font-weight-400">{{ __('Showing calculated shipping rate options based on shipment details.') }}</span></h6>
    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="modal-body pt-0">
    <div class="row">
        @foreach($shippingRates as $key => $shippingRate)
            @if($key === 'cheapest_rate')
                @if(!empty($shippingRate))
                    <div class="col-12">
                        <h3 class="m-0 mt-4">{{ __('Cheapest Shipping Rate') }}</h3>
                    </div>
                    <div class="col-12 mt-4 cursor-pointer shipping-rate-item" data-shipping-method-id="{{ $shippingRate['shipping_method_id'] }}" data-shipping-method-name="{{ $shippingRate['carrier'].' - '.$shippingRate['service'] }}" data-rate="{{ Arr::get($shippingRate, 'rate') }}" data-rate-id="{{ Arr::get($shippingRate, 'rate_id') }}">
                        <div class="row m-0 border rounded">
                            <div class="col-8 p-2 d-flex align-items-center">
                                <img alt="{{ __('Shipping Rate') }}" src="/img/no-image.png" class="img-thumbnail rounded">
                                <h3 class="m-0 ml-2">{{ $shippingRate['carrier'] }} {{ $shippingRate['service'] }}<br><span class="font-xs font-weight-400 m-0 {{ $shippingRate['delivery_days'] === null ? 'd-none' : '' }}">{{ $shippingRate['delivery_days'] }} {{ $shippingRate['delivery_days'] > 1 ? __(' business days') : __(' business day') }}</span></h3>
                            </div>
                            <div class="col-4 p-2 pr-3 d-flex align-items-center justify-content-end">
                                <span class="font-md font-weight-500">{{ $shippingRate['currency'] }} {{ Arr::get($shippingRate, 'rate') }}</span>
                            </div>
                            <div class="col-12 h-100 w-100 d-flex align-items-center justify-content-center shipping-rate-item-button">
                                <a class="btn bg-blue text-white">{{ __('Select') }}</a>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                @if(isset($shippingRate['errors']))
                    <div class="col-12 mt-4 cursor-pointer">
                        <div class="row m-0 border rounded">
                            <div class="col-8 p-2 d-flex align-items-center">
                                <img alt="{{ __('Shipping Rate') }}" src="/img/no-image.png" class="img-thumbnail rounded">
                                <h3 class="m-0 ml-2">{{ $key }}</h3>
                            </div>
                            <div class="col-4 p-2 pr-3 d-flex align-items-center justify-content-end">
                                <span class="font-md font-weight-500"><i class="picon-alert-circled-light text-primary ml-1" data-toggle="tooltip" data-placement="top" data-html="true" title="
                                    @foreach($shippingRate['errors'] as $error)
                                        {{ $error['message'] }}<br>
                                    @endforeach
                                "></i></span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="col-12">
                        <h3 class="m-0 mt-4">{{ $key }} {{ __('Options') }}</h3>
                    </div>
                    @foreach($shippingRate as $option)
                        <div class="col-12 mt-4 cursor-pointer shipping-rate-item" data-shipping-method-id="{{ $option['shipping_method_id'] }}" data-shipping-method-name="{{ $key.' - '.$option['service'] }}" data-rate="{{ Arr::get($option, 'rate') }}" data-rate-id="{{ Arr::get($option, 'rate_id') }}">
                            <div class="row m-0 border rounded">
                                <div class="col-8 p-2 d-flex align-items-center">
                                    <img alt="{{ __('Shipping Rate') }}" src="/img/no-image.png" class="img-thumbnail rounded">
                                    <h3 class="m-0 ml-2">{{ $key }} {{ $option['service'] }}<br><span class="font-xs font-weight-400 m-0 {{ $option['delivery_days'] === null ? 'd-none' : '' }}">{{ $option['delivery_days'] }} {{ $option['delivery_days'] > 1 ? __(' business days') : __(' business day') }}</span></h3>
                                </div>
                                <div class="col-4 p-2 pr-3 d-flex align-items-center justify-content-end">
                                    <span class="font-md font-weight-500">{{ $option['currency'] }} {{ Arr::get($option, 'rate') }}</span>
                                </div>
                                <div class="col-12 h-100 w-100 d-flex align-items-center justify-content-center shipping-rate-item-button">
                                    <a class="btn bg-blue text-white">{{ __('Select') }}</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            @endif
        @endforeach
    </div>
</div>
