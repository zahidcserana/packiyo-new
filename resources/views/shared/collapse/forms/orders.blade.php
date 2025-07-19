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
    <label for="" class="font-xs">{{ __('Required ship date') }}</label>
    <input class="form-control datetimepicker" type="text" value="" name="ship_before">
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Order Status') }}</label>
    <select name="order_status" id="" class="form-control" id="orderStatuses">
        <option value="0">{{ __('All') }}</option>
        @foreach(\App\Models\Order::ORDER_STATUSES as $id => $status)
            <option value="{{ $id }}">{{ $status }}</option>
        @endforeach
        @foreach ($data['order_statuses'] as $status)
            <option value="{{ $status->id }}">{{ $status->name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Ready To Ship') }}</label>
    <select name="ready_to_ship" class="form-control">
        <option value="all">{{ __('All') }}</option>
        <option value="1">{{ __('Yes') }}</option>
        <option value="0">{{ __('No') }}</option>
    </select>
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Ready To Pick') }}</label>
    <select name="ready_to_pick" class="form-control">
        <option value="all">{{ __('All') }}</option>
        <option value="1">{{ __('Yes') }}</option>
        <option value="0">{{ __('No') }}</option>
    </select>
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('In Tote') }}</label>
    <select name="in_tote" class="form-control">
        <option value="">{{ __('All') }}</option>
        <option value="1">{{ __('Yes') }}</option>
        <option value="0">{{ __('No') }}</option>
    </select>
</div>
<div class="form-group col-12 col-md-3">
    <div class="custom-form-checkbox">
        <input name="priority" id="priority" type="checkbox" value="1">
        <label for="priority" class="font-xs">{{ __('Priority') }}</label>
    </div>
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Backordered') }}</label>
    <select name="backordered" class="form-control">
        <option value="">{{ __('All') }}</option>
        <option value="0">{{ __('Yes') }}</option>
        <option value="1">{{ __('No') }}</option>
    </select>
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
@include('shared.forms.countrySelect', [
    'containerClass' => 'col-12 col-md-3',
    'name' => 'country',
    'allowClear' => true
])
<div class="form-group col-12 col-md-3 d-flex">
    <div class="w-50 mr-1">
        <label for="" class="font-xs">{{ __('Weight From') }}</label>
        <input type="number" class="form-control" name="weight_from">
    </div>
    <div class="w-50 ">
        <label for="" class="font-xs">{{ __('Weight To') }}</label>
        <input type="number" class="form-control" name="weight_to">
    </div>
</div>
<div class="form-group col-12 col-md-3">
    <label for="any_hold" class="font-xs">{{ __('Order Holds') }}</label>
    <select name="any_hold" class="form-control" id="any_hold">
        <option value="all">{{ __('All') }}</option>
        <option value="any_hold">{{ __('Any') }}</option>
        <option value="operator_hold">{{ __('Operator hold') }}</option>
        <option value="payment_hold">{{ __('Payment hold') }}</option>
        <option value="address_hold">{{ __('Address hold') }}</option>
        <option value="fraud_hold">{{ __('Fraud hold') }}</option>
        <option value="allocation_hold">{{ __('Allocation hold') }}</option>
        <option value="hold_until">{{ __('Hold until') }}</option>
        <option value="none">{{ __('None') }}</option>
    </select>
</div>
<div class="form-group col-12 col-md-3">
    <label for="skus" class="font-xs">{{ __('SKU') }}</label>
    <input type="text" name="skus" class="form-control" placeholder="sku1,sku2,...,*" />
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Archived') }}</label>
    <select name="archived" class="form-control">
        <option value="">{{ __('All') }}</option>
        <option value="0">{{ __('Yes') }}</option>
        <option value="1" selected>{{ __('No') }}</option>
    </select>
</div>

<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Transfer Order') }}</label>
    <select name="transfer_order" class="form-control">
        <option value="" selected>{{ __('All') }}</option>
        <option value="0">{{ __('Yes') }}</option>
        <option value="1">{{ __('No') }}</option>
    </select>
</div>

<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Warehouse') }}</label>
    <select name="warehouse_id" class="form-control">
        <option value="" selected>{{ __('All') }}</option>
        @foreach($data['warehouses'] as $warehouse)
            <option value="{{ $warehouse->id }}">{{ $warehouse->contactInformation->name }}</option>
        @endforeach
    </select>
</div>
@include('shared.forms.tagSelect')
<div class="form-group col-12 col-md-2">
    <div class="custom-form-checkbox">
        <input name="disabled_on_picking_app" id="disabled_on_picking_app" type="checkbox" value="1">
        <label for="disabled_on_picking_app" class="font-xs">{{ __('Disabled on picking app') }}</label>
    </div>
</div>
@if (!isset($sessionCustomer) || $sessionCustomer->is3pl() || $sessionCustomer->isStandalone())
    <div class="form-group col-12 col-md-3">
        <label for="automation" class="font-xs">{{ __('Automation') }}</label>
        <select name="automation" class="form-control">
            <option value="0">{{ __('All') }}</option>
            @foreach($data['automations'] as $automation)
                <option>{{ $automation }}</option>
            @endforeach
        </select>
    </div>
@endif
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Locked') }}</label>
    <select name="locked" class="form-control">
        <option value="">{{ __('All') }}</option>
        <option value="1">{{ __('Locked') }}</option>
        <option value="0">{{ __('Unlocked') }}</option>
    </select>
</div>
