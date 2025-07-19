@if(isset($orderChannelDetails['data']['scheduler_information']) && isset($orderChannelDetails['data']['scheduler_information']['enabled']) && $orderChannelDetails['data']['scheduler_information']['enabled'])
@php
    $schedulers = collect($orderChannelDetails['data']['scheduler_information']['schedulers']);
@endphp
<div class="card p-4 strech-container">
    <div class="border-bottom  py-2 d-flex">
        <h6 class="modal-title text-black text-left">
            {{ __('Manage Schedulers') }}
        </h6>
    </div>
    <div class="d-flex py-3 justify-content-between flex-column {{ $orderChannel->is_disabled ? 'disabled-look-without-clickable' : '' }}">
        <p>
            {!! __('Examples how to set up CRON: <a href=":link" target="_blaml">:link</a>', ['link' => 'https://crontab.guru/examples.html']) !!} <br />
            {{ __('ATTN: Our cron is more flexible than the examples in the link so make sure to add "0 " before the expression copied from examples - so that there are 6 segments in the expression instead of 5.') }}<br />
            {{ __('For instance: you want to set up cron expression for every night at 2 AM. Crontab.guru example will say use "0 2 * * *" but you should write "0 0 2 * * *"') }}<br />
            {{ __('Expression dates are in GMT+0 time zone') }}
        </p>
        <div class="align-items-center mb-5">
            @php $productSyncScheduler = $schedulers->where("data_sync_type", "PRODUCT_SYNC")->first(); @endphp
            <form action="{{ route($productSyncScheduler && $productSyncScheduler['active'] ? 'order_channels.disableScheduler' : 'order_channels.enableScheduler', ['orderChannel' => $orderChannel->id]) }}" method="post">
                @csrf
                <div class="product-sync-cron-loading-img-div text-center d-none">
                    <img width="50px" src="{{ asset('img/loading.gif') }}">
                </div>
                <div class="row justify-content-between">
                    <input type="hidden" name="type" value="PRODUCT_SYNC">
                    <div class="col-6">
                        @include('shared.forms.input', [
                            'containerClass' => '',
                            'name' => 'cron_expression',
                            'label' => 'Product Sync Cron Expression',
                            'value' => $productSyncScheduler ? $productSyncScheduler["cron_expression"] : "",
                            'class' => 'form-control-sm text-center cron-expression'
                        ])
                    </div>
                    <div class="col-4 py-3 justify-content-start">
                        @include('shared.forms.checkbox', [
                            'name' => 'product_sync_cron_checkbox',
                            'label' => __('Enable'),
                            'checked' => $productSyncScheduler && $productSyncScheduler['active'] ? true : false,
                            'containerClass' => "cron-checkbox"
                        ])
                    </div>
                </div>
            </form>
            @if($productSyncScheduler && $productSyncScheduler['active'])
                {{ __('Next execution time: :nextExecutionTime', ['nextExecutionTime' => user_date_time($productSyncScheduler['next_execution_time'], true)]) }}
            @endif
        </div>
        <div class="align-items-center mb-5">
            @php $orderSyncScheduler = $schedulers->where("data_sync_type", "ORDER_SYNC")->first(); @endphp
            <form action="{{ route($orderSyncScheduler && $orderSyncScheduler['active'] ? 'order_channels.disableScheduler' : 'order_channels.enableScheduler', ['orderChannel' => $orderChannel->id]) }}" method="post">
                @csrf
                <div class="order-sync-cron-loading-img-div text-center d-none">
                    <img width="50px" src="{{ asset('img/loading.gif') }}">
                </div>
                <div class="row justify-content-between">
                    <input type="hidden" name="type" value="ORDER_SYNC">
                    <div class="col-6">
                        @include('shared.forms.input', [
                            'containerClass' => '',
                            'name' => 'cron_expression',
                            'label' => 'Order Sync Cron Expression',
                            'value' => $orderSyncScheduler ? $orderSyncScheduler["cron_expression"] : "",
                            'class' => 'form-control-sm text-center cron-expression'
                        ])
                    </div>

                    <div class="col-4 py-4 justify-content-start">
                        @include('shared.forms.checkbox', [
                            'name' => 'order_sync_cron_checkbox',
                            'label' => __('Enable'),
                            'checked' => $orderSyncScheduler && $orderSyncScheduler['active'] ? true : false,
                            'containerClass' => "cron-checkbox"
                        ])
                    </div>
                </div>
            </form>
            @if($orderSyncScheduler && $orderSyncScheduler['active'])
                {{ __('Next execution time: :nextExecutionTime', ['nextExecutionTime' => user_date_time($orderSyncScheduler['next_execution_time'], true)]) }}
            @endif
        </div>
        <div class="align-items-center mb-5">
            @php $inventorySyncScheduler = $schedulers->where("data_sync_type", "INVENTORY_SYNC")->first(); @endphp
            <form action="{{ route($inventorySyncScheduler && $inventorySyncScheduler['active'] ? 'order_channels.disableScheduler' : 'order_channels.enableScheduler', ['orderChannel' => $orderChannel->id]) }}" method="post">
                @csrf
                <div class="inventory-sync-cron-loading-img-div text-center d-none">
                    <img width="50px" src="{{ asset('img/loading.gif') }}">
                </div>
                <div class="row justify-content-between">
                    <input type="hidden" name="type" value="INVENTORY_SYNC">
                    <div class="col-6">
                        @include('shared.forms.input', [
                            'containerClass' => '',
                            'name' => 'cron_expression',
                            'label' => 'Inventory Sync Cron Expression',
                            'value' => $inventorySyncScheduler ? $inventorySyncScheduler["cron_expression"] : "",
                            'class' => 'form-control-sm text-center cron-expression'
                        ])
                    </div>
                    <div class="col-4 py-3 justify-content-start">
                        @include('shared.forms.checkbox', [
                            'name' => 'inventory_sync_cron_checkbox',
                            'label' => __('Enable'),
                            'checked' => $inventorySyncScheduler && $inventorySyncScheduler['active'] ? true : false,
                            'containerClass' => "cron-checkbox"
                        ])
                    </div>
                </div>
            </form>
            @if($inventorySyncScheduler && $inventorySyncScheduler['active'])
                {{ __('Next execution time: :nextExecutionTime', ['nextExecutionTime' => user_date_time($inventorySyncScheduler['next_execution_time'], true)]) }}
            @endif
        </div>
        <div class="align-items-center mb-5">
            @php $shipmentSyncScheduler = $schedulers->where("data_sync_type", "SHIPMENT_SYNC")->first(); @endphp
            <form action="{{ route($shipmentSyncScheduler && $shipmentSyncScheduler['active'] ? 'order_channels.disableScheduler' : 'order_channels.enableScheduler', ['orderChannel' => $orderChannel->id]) }}" method="post">
                @csrf
                <div class="shipment-sync-cron-loading-img-div text-center d-none">
                    <img width="50px" src="{{ asset('img/loading.gif') }}">
                </div>
                <div class="row justify-content-between">
                <input type="hidden" name="type" value="SHIPMENT_SYNC">
                    <div class="col-6">
                        @include('shared.forms.input', [
                            'containerClass' => '',
                            'name' => 'cron_expression',
                            'label' => 'Shipment Sync Cron Expression',
                            'value' => $shipmentSyncScheduler ? $shipmentSyncScheduler["cron_expression"] : "",
                            'class' => 'form-control-sm text-center cron-expression'
                        ])
                    </div>
                    <div class="col-4 py-3 justify-content-start">
                        @include('shared.forms.checkbox', [
                            'name' => 'shipment_sync_cron_checkbox',
                            'label' => __('Enable'),
                            'checked' => $shipmentSyncScheduler && $shipmentSyncScheduler['active'] ? true : false,
                            'containerClass' => "cron-checkbox"
                        ])
                    </div>
                </div>
            </form>
            @if($shipmentSyncScheduler && $shipmentSyncScheduler['active'])
                {{ __('Next execution time: :nextExecutionTime', ['nextExecutionTime' => user_date_time($shipmentSyncScheduler['next_execution_time'], true)]) }}
            @endif
        </div>
    </div>
</div>
@endif
