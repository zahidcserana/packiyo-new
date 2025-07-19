@if(!isset($shippingBox->customer) && !isset($sessionCustomer))
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
    <input type="hidden" name="customer_id" value="{{ $shippingBox->customer->id ?? $sessionCustomer->id }}" class="customer_id" />
@endif

<div class="row">
    <div class="col-6">
        <label for="type" class="form-control-label text-neutral-text-gray font-weight-600 font-xs">{{ __('Type') }}</label>
        <select name="type" class="form-control" id="type">
            @foreach(\App\Models\ShippingBox::TYPES as $key => $type)
                <option {{ (!empty($shippingBox) && $shippingBox->type == $key) ? 'selected' : '' }} value="{{ $key }}">{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6">
        @include('shared.forms.input', [
        'name' => 'name',
        'label' => __('Name'),
        'value' => $shippingBox->name ?? ''
        ])
    </div>
</div>
<div class="row">
    <div class="col-6">
        @include('shared.forms.input', [
            'name' => 'weight',
            'label' => __('Weight'),
            'value' => $shippingBox->weight ?? ''
        ])
    </div>
    <div class="col-6 pt-4">
        @include('shared.forms.editCheckbox', [
            'name' => 'weight_locked',
            'label' => __('Weight locked'),
            'checked' => (! empty(old('weight_locked'))) ? old('weight_locked') :  ($shippingBox->weight_locked ?? ''),
            'noCenter' => true,
            'noYes' => true,
            'noBorder' => true,
            'checkboxFirst' => true
        ])
    </div>
</div>
<div class="row">
    <div class="col-6">
        @include('shared.forms.input', [
            'name' => 'length',
            'label' => __('Length'),
            'value' => $shippingBox->length ?? ''
        ])
    </div>
    <div class="col-6 pt-4">
        @include('shared.forms.editCheckbox', [
            'name' => 'length_locked',
            'label' => __('Length locked'),
            'checked' => (! empty(old('length_locked'))) ? old('length_locked') :  ($shippingBox->length_locked ?? ''),
            'noCenter' => true,
            'noYes' => true,
            'noBorder' => true,
            'checkboxFirst' => true
        ])
    </div>
</div>
<div class="row">
    <div class="col-6">
        @include('shared.forms.input', [
            'name' => 'width',
            'label' => __('Width'),
            'value' => $shippingBox->width ?? ''
        ])
    </div>
    <div class="col-6 pt-4">
        @include('shared.forms.editCheckbox', [
            'name' => 'width_locked',
            'label' => __('Width locked'),
            'checked' => (! empty(old('width_locked'))) ? old('width_locked') :  ($shippingBox->width_locked ?? ''),
            'noCenter' => true,
            'noYes' => true,
            'noBorder' => true,
            'checkboxFirst' => true
        ])
    </div>
</div>
<div class="row">
    <div class="col-6">
        @include('shared.forms.input', [
            'name' => 'height',
            'label' => __('Height'),
            'value' => $shippingBox->height ?? ''
        ])
    </div>
    <div class="col-6 pt-4">
        @include('shared.forms.editCheckbox', [
            'name' => 'height_locked',
            'label' => __('Height locked'),
            'checked' => (! empty(old('height_locked'))) ? old('height_locked') :  ($shippingBox->height_locked ?? ''),
            'noCenter' => true,
            'noYes' => true,
            'noBorder' => true,
            'checkboxFirst' => true
        ])
    </div>
</div>
<div class="row">
    <div class="col-6">
        @include('shared.forms.input', [
            'name' => 'cost',
            'label' => __('Cost per Box'),
            'value' => $shippingBox->cost ?? ''
        ])
    </div>
</div>
