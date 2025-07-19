@if(!isset($shippingMethodMapping->customer) && !isset($sessionCustomer))
    <div class="searchSelect">
        @include('shared.forms.new.ajaxSelect', [
        'url' => route('user.getCustomers'),
        'name' => 'customer_id',
        'className' => 'ajax-user-input customer_id',
        'placeholder' => __('Select customer'),
        'label' => __('Customer'),
        'default' => [
            'id' => old('customer_id'),
            'text' => ''
        ],
        'fixRouteAfter' => '.ajax-user-input.customer_id'
    ])
    </div>
@else
    <input type="hidden" name="customer_id" value="{{ $shippingMethodMapping->customer->id ?? $sessionCustomer->id }}" class="customer_id" />
@endif

@include('shared.forms.input', [
    'name' => 'shipping_method_name',
    'label' => __('Shop Shipping Method Name'),
    'value' => $shippingMethodMapping->shipping_method_name ?? $shippingMethodName,
    'readOnly' => !empty($edit) || ($shippingMethodMapping->shipping_method_name ?? $shippingMethodName) ? 'readonly' : ''
])

@include('shared.forms.ajaxSelect', [
    'url' => route('shipping_method_mapping.filterShippingMethods', ['customer' => $shippingMethodMapping->customer->id ?? $sessionCustomer->id ?? 1, 'include_cheapest' => true]),
    'name' => 'shipping_method_id',
    'className' => 'ajax-user-input enabled-for-customer shipping_method_id',
    'placeholder' => __('Search'),
    'label' => __('Shipping Method'),
    'default' => [
        'id' => $shippingMethodId ?? '',
        'text' => $shippingMethodName ?? ''
    ],
    'fixRouteAfter' => '.ajax-user-input.customer_id'
])

@include('shared.forms.ajaxSelect', [
    'url' => route('shipping_method_mapping.filterShippingMethods', ['customer' => $shippingMethodMapping->customer->id ?? $sessionCustomer->id ?? 1]),
    'name' => 'return_shipping_method_id',
    'className' => 'ajax-user-input enabled-for-customer return_shipping_method_id',
    'placeholder' => __('Search'),
    'label' => __('Return Shipping Method'),
    'allowClear' => true,
    'default' => [
        'id' => $shippingMethodMapping->returnShippingMethod->id ?? old('return_shipping_method_id'),
        'text' => isset($shippingMethodMapping->returnShippingMethod) ? $shippingMethodMapping->returnShippingMethod->getCarrierNameAndNameAttribute() : ''
    ],
    'fixRouteAfter' => '.ajax-user-input.customer_id'
])
