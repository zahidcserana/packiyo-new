@if(!isset($sessionCustomer))
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
@else
    <input type="hidden" name="customer_id" value="{{ $sessionCustomer->id }}" class="customer_id" />
@endif
