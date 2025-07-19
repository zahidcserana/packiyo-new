@include('shared.collapse.forms._customer')
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Start Date') }}</label>
    <input class="form-control datetimepicker" type="text" value="{{ user_date_time(now()->subWeeks(2)->startOfDay()) }}" name="start_date">
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('End Date') }}</label>
    <input class="form-control datetimepicker" type="text" value="" name="end_date">
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">Tote</label>
    <input class="form-control" type="text" name="tote">
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">Lot</label>
    <input class="form-control" type="text" name="lot">
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Location') }}</label>
    <input class="form-control" type="text" name="location">
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">Packer</label>
    <input class="form-control" type="text" name="packer">
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Carrier') }}</label>
    <select name="shipping_carrier" class="form-control">
        <option value="0">{{ __('All') }}</option>
        @foreach($data['shipping_carriers'] as $shippingCarrier)
            <option>{{ $shippingCarrier }}</option>
        @endforeach
    </select>
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Shipping Method') }}</label>
    <select name="shipping_method" class="form-control">
        <option value="0">{{ __('All') }}</option>
        @foreach($data['shipping_methods'] as $shippingMethod)
            <option>{{ $shippingMethod }}</option>
        @endforeach
    </select>
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Order Channel') }}</label>
    <select name="order_channel" class="form-control">
        <option value="0">{{ __('All') }}</option>
        @foreach($data['order_channels'] as $orderChannel)
            <option>{{ $orderChannel }}</option>
        @endforeach
    </select>
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Shipping Box') }}</label>
    <select name="shipping_box" class="form-control">
        <option value="0">{{ __('All') }}</option>
        @foreach($data['shipping_boxes'] as $shippingBox)
            <option>{{ $shippingBox }}</option>
        @endforeach
    </select>
</div>
