<div class="card p-4 strech-container">
    <div class="border-bottom  py-2 d-flex">
        <h6 class="modal-title text-black text-left">
            {{ __('Manage Syncs') }}
        </h6>
    </div>
    <div class="d-flex text-center py-3 justify-content-between flex-column">
        <div class="py-3 align-items-center">
            <div class="products-sync-loading-img-div text-center d-none">
                <img width="50px" src="{{ asset('img/loading.gif') }}">
            </div>
            <button type="button" id="products-sync-button" data-order-channel-id="{{$orderChannel->id}}" class="btn bg-logoOrange borderOrange text-white mt-1 mx-auto px-4 px-md-5 font-weight-700">{{ __('Sync Products') }}</button>                        
        </div>

        <div class="align-items-center">
            <div class="product-sync-by-id-loading-img-div text-center d-none">
                <img width="50px" src="{{ asset('img/loading.gif') }}">
            </div>
            <div class="row justify-content-center">
                <div class="col-4">
                    @include('shared.forms.input', [
                        'name' => 'product_id',
                        'label' => '',
                        'value' => ''
                    ])
                </div>
                <div class="col-4 py-3 justify-content-start">
                    <button type="button" id="product-sync-by-id-button" data-order-channel-id="{{$orderChannel->id}}" class="btn bg-logoOrange borderOrange text-white mt-1 mx-auto px-4 px-md-5 font-weight-700">{{ __('Sync Product by ID') }}</button>
                </div>
            </div>
        </div>

        <div class="align-items-center">
            <div class="product-sync-by-sku-loading-img-div text-center d-none">
                <img width="50px" src="{{ asset('img/loading.gif') }}">
            </div>
            <div class="row justify-content-center">
                <div class="col-4">
                    @include('shared.forms.input', [
                        'name' => 'product_sku',
                        'label' => '',
                        'value' => ''
                    ])
                </div>
                <div class="col-4 py-3 justify-content-start">
                    <button type="button" id="product-sync-by-sku-button" data-order-channel-id="{{$orderChannel->id}}" class="btn bg-logoOrange borderOrange text-white mt-1 mx-auto px-4 px-md-5 font-weight-700">{{ __('Sync Product by SKU') }}</button>
                </div>
            </div>
        </div>

        <div class="py-3 align-items-center">
            <div class="inventories-sync-loading-img-div text-center d-none">
                <img width="50px" src="{{ asset('img/loading.gif') }}">
            </div>
            <button type="button" id="inventories-sync-button" data-order-channel-id="{{$orderChannel->id}}" class="btn bg-logoOrange borderOrange text-white mt-1 mx-auto px-4 px-md-5 font-weight-700">{{ __('Sync Inventories') }}</button>                        
        </div>

        <div class="align-items-center">
            <div class="order-sync-by-number-loading-img-div text-center d-none">
                <img width="50px" src="{{ asset('img/loading.gif') }}">
            </div>
            <div class="row justify-content-center">
                <div class="col-4">
                    @include('shared.forms.input', [
                        'name' => 'order_number',
                        'label' => '',
                        'value' => ''
                    ])
                </div>
                <div class="col-4 py-3 justify-content-start">
                    <button type="button" id="order-sync-by-number-button" data-order-channel-id="{{$orderChannel->id}}" class="btn bg-logoOrange borderOrange text-white mt-1 mx-auto font-weight-700">{{ __('Sync Order by Number') }}</button>
                </div>
            </div>
        </div>

        <div class="align-items-center">
            <div class="order-sync-date-from-loading-img-div text-center d-none">
                <img width="50px" src="{{ asset('img/loading.gif') }}">
            </div>
            <div class="row justify-content-center">
                <div class="col-4">
                    @include('shared.forms.input', [
                        'containerClass' => '',
                        'name' => 'order_sync_date_from',
                        'label' => '',
                        'value' => user_date_time(now()->subDays(1)->startOfDay()),
                        'class' => 'dt-daterangepicker text-center'
                    ])
                </div>
                <div class="col-4 py-3 justify-content-start">
                    <button type="button" id="order-sync-date-from-button" data-order-channel-id="{{$orderChannel->id}}" class="btn bg-logoOrange borderOrange text-white mt-1 mx-auto font-weight-700">{{ __('Sync Orders from Date') }}</button>
                </div>
            </div>
        </div>

        <div class="align-items-center">
            <div class="shipment-sync-loading-img-div text-center d-none">
                <img width="50px" src="{{ asset('img/loading.gif') }}">
            </div>
            <div class="row justify-content-center">
                <div class="col-4">
                    @include('shared.forms.input', [
                        'containerClass' => '',
                        'name' => 'shipment_sync_date_from',
                        'label' => '',
                        'value' => user_date_time(now()->subDays(1)->startOfDay()),
                        'class' => 'dt-daterangepicker text-center'
                    ])
                </div>
                <div class="col-4 py-3 justify-content-start">
                    <button type="button" id="shipment-sync-button" data-order-channel-id="{{$orderChannel->id}}" class="btn bg-logoOrange borderOrange text-white mt-1 mx-auto px-4 px-md-5 font-weight-700">{{ __('Sync Shipments') }}</button>
                </div>
            </div>
        </div>

    </div>
</div>