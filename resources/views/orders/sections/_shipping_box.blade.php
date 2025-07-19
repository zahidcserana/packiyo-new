@include('shared.forms.select', [
    'name' => 'shipping_box_id',
    'label' => '',
    'className' => 'shipping_box_id',
    'placeholder' => __('Select a shipping box'),
    'error' => !empty($errors->get('shipping_box_id')) ? $errors->first('shipping_box_id') : false,
    'value' => $order->shipping_box_id ?? old('shipping_box_id'),
    'options' => ['' => __('')] + $shippingBoxes->pluck('name', 'id')->toArray()
])
