<div class="d-flex">
    <div class="row w-100">
        <div class="col-6">
            <div class="w-100" id="customers_container">
                @if(!isset($sessionCustomer))
                    @include('shared.forms.new.ajaxSelect', [
                        'url' => route('user.getCustomers'),
                        'name' => 'customer_id',
                        'className' => 'ajax-user-input customer_id',
                        'placeholder' => __('Select customer'),
                        'label' => __('Customer'),
                        'default' => [
                            'id' => $lot->customer_id ?? null,
                            'text' => $lot->customer->contactInformation->name ?? null,
                        ],
                        'fixRouteAfter' => '.ajax-user-input.customer_id',
                        'id' => 'lot_customer_id',
                    ])
                @else
                    <input
                        type="hidden" name="customer_id" id="lot_customer_id" value="{{ $sessionCustomer->id }}"
                        class="customer_id"
                    />
                @endif
                <input type="hidden" id="customers_base_ajax_url" value="{{ route('user.getCustomers') }}"/>
            </div>
            <div class="w-100" id="suppliers_container">
                @include('shared.forms.new.ajaxSelect', [
                    'url' => route('product.filterSuppliers'),
                    'name' => 'supplier_id',
                    'className' => 'ajax-user-input supplier_id',
                    'placeholder' => __('Select supplier'),
                    'label' => __('Supplier'),
                    'fixRouteAfter' => '.ajax-user-input.supplier_id',
                    'id' => 'lot_supplier_id',
                    'default' => [
                        'id' => $lot->supplier_id ?? null,
                        'text' => $lot->supplier->contactInformation->name ?? null,
                    ],
                ])
            </div>
            <div class="w-100" id="products_container">
                @include('shared.forms.new.ajaxSelect', [
                    'url' => route('product.filterBySupplier'),
                    'name' => 'product_id',
                    'className' => 'ajax-user-input product_id',
                    'placeholder' => __('Select product'),
                    'label' => __('Product'),
                    'fixRouteAfter' => '.ajax-user-input.product_id',
                    'id' => 'lot_product_id',
                    'default' => [
                        'id' => $lot->product_id ?? null,
                        'text' => $lot->product->name ?? null,
                    ],
                ])
            </div>
        </div>
        <div class="col-6">
            <div class="w-100">
                @include('shared.forms.input', [
                    'name' => 'name',
                    'label' => __('Id'),
                    'value' => $lot->name ?? '',
                ])
            </div>
            <div class="w-100">
                @include('shared.forms.input', [
                    'name' => 'item_price',
                    'label' => __('Item price'),
                    'value' => $lot->item_price ?? '',
                ])
            </div>
            <div class="w-100">
                @include('shared.forms.input', [
                    'name' => 'expiration_date',
                    'label' => __('Expiration date'),
                    'type' => 'date',
                    'error' => ! empty($errors->get('expiration_date')) ? $errors->first('expiration_date') : false,
                    'value' => $lot->expiration_date ?? '',
                ])
            </div>
        </div>
    </div>

</div>
