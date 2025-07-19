@include('shared.forms.select', [
    'name' => 'shipping_method_id',
    'label' => '',
    'className' => 'shipping_method_id',
    'placeholder' => __('Select a shipment method'),
    'error' => !empty($errors->get('shipping_method_id')) ? $errors->first('shipping_method_id') : false,
    'value' => $order->shipping_method_id ?? old('shipping_method_id'),
    'options' => ['generic' => __('Generic')] + $shippingMethods->pluck('carrierNameAndName', 'id')->toArray()
])

@if (!$order->shipping_method_id && $order->mappedShippingMethod && $order->mappedShippingMethod->type)
    <p>{{ __('Mapped to :method', ['method' => Arr::get(App\Models\ShippingMethodMapping::CHEAPEST_SHIPPING_METHODS, $order->mappedShippingMethod->type)]) }}</p>
@endif
