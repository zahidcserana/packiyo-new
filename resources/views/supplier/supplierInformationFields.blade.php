<div class="d-flex flex-column">
    <div class="row">
         @include('shared.forms.select', [
            'name' => 'currency',
            'placeholder' => __('Select currency'),
            'dataId' => 'currency_id',
            'containerClass' => 'col-12 col-md-6',
            'label' => __('Currency'),
            'value' => $supplier->currency ?? '',
            'options' => Webpatser\Countries\Countries::all()->pluck('currency_code', 'currency_code'),
            'attributes' => [
                'data-no-select2' => true,
            ]
        ])
        @include('shared.forms.input', [
            'name' => 'internal_note',
            'label' => __('Internal note'),
            'containerClass' => 'col-12 col-md-6',
            'value' => $supplier->internal_note ?? ''
       ])
        @include('shared.forms.input', [
            'name' => 'default_purchase_order_note',
            'containerClass' => 'col-12 col-md-6',
            'label' => __('Default PO note'),
            'value' => $supplier->default_purchase_order_note ?? ''
        ])
    </div>
</div>

