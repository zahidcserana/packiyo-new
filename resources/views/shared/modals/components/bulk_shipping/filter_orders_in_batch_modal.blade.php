@php
$shippingMethodsSelect = $shippingMethods->pluck('name', 'id')->toArray();
@endphp
<div class="modal fade confirm-dialog" id="filter-orders-in-batch-modal" role="dialog" {{ $dataKeyboard ?? '' ? 'data-backdrop=static data-keyboard=false' : '' }}>
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content productForm">
            <div class="modal-header px-0">
                <div class="mx-4 pb-4 d-flex w-100 border-bottom-gray">
                    <h6 class="modal-title text-black text-left"
                        id="modal-title-notification">{{ __('Filter orders') }}</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                        <span aria-hidden="true" class="text-black">&times;</span>
                    </button>
                </div>
            </div>
            <form id="filter-orders-in-batch-form">
                <div class="modal-body text-center py-3 overflow-auto">
                    @include('shared.forms.select', [
                           'name' => 'batch_filter.bulk_ship_order_status',
                           'containerClass' => 'mx-2',
                           'value' => '',
                           'label' => __('Status'),
                           'options' => [
                               'all' => __('All'),
                               'shipped' => __('Shipped'),
                               'not_shipped' => __('Not Shipped'),
                               'failed' => __('Failed')
                           ]
                        ])
                </div>
                <div class="modal-body text-center py-3 overflow-auto">
                    @include('shared.forms.select', [
                           'name' => 'batch_filter.shipping_carrier_id',
                           'class' => 'batch-filter-shipping-carrier',
                           'containerClass' => 'mx-2',
                           'value' => 'all',
                           'label' => __('Shipping Carrier'),
                           'options' => [
                               'all' => 'All'
                           ] + $shippingCarriers
                        ])
                </div>
                <div class="modal-body text-center py-3 overflow-auto">
                    @include('shared.forms.select', [
                           'name' => 'batch_filter.shipping_method_id',
                           'class' => 'batch-filter-shipping-method',
                           'containerClass' => 'mx-2',
                           'value' => '',
                           'label' => __('Shipping Method'),
                           'options' => [
                               'all' => 'All'
                           ] + $shippingMethodsSelect
                        ])
                </div>
                <div class="modal-footer">
                    <button type="button"
                            class="btn bg-logoOrange text-white mx-auto px-5 font-weight-700"
                            id="apply-filter-button"
                    >
                        {{ __('Apply') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
