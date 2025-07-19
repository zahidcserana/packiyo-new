<div class="row inputs-container">
    @if(!isset($sessionCustomer))
        <div class="col-12 col-xl-6">
            <div class="searchSelect">
                @include('shared.forms.new.ajaxSelect', [
                'url' => route('user.getCustomers'),
                'name' => 'customer_id',
                'className' => 'ajax-user-input customer-id-select',
                'placeholder' => __('Select customer'),
                'label' => __('Customer'),
                'default' => [
                    'id' => $returnStatus ? $returnStatus->customer_id : null,
                    'text' => $returnStatus ? $returnStatus->customer->contactInformation->name : null
                ],
                'fixRouteAfter' => '.ajax-user-input.customer_id'
            ])
            </div>
        </div>
    @else
        <input type="hidden" name="customer_id" value="{{ $sessionCustomer->id }}" class="customer_id" />
    @endif
    <div class="col-12 col-xl-6">
        @include('shared.forms.input', [
            'name' => 'name',
            'label' => __('Name'),
            'value' => $returnStatus->name ?? ''
        ])
    </div>
    <div class="col-12 col-xl-6">
        @include('shared.forms.colorPicker', [
            'name' => 'color',
            'label' => __('Choose Status Color'),
            'value' => $returnStatus->color ?? ''
        ])
    </div>
</div>
