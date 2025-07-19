<div class="card p-4 h-lg-100 strech-container {{ $orderChannel->is_disabled ? 'disabled-look-without-clickable' : '' }}">
    <div class="border-bottom  py-2 d-flex">
        <h6 class="modal-title text-black text-left">
            {{ __('Manage Webhooks') }}
        </h6>
    </div>
    @if(!is_null($orderChannelInfo) && $orderChannelInfo['success'] && $orderChannelInfo['data']['webhook_info'] && $orderChannelInfo['data']['webhook_info']['webhook_creation_supported'])
        <div class="d-flex text-center py-3 justify-content-between flex-column">
            <div class="text-left">
                <h4 class="d-inline-block">{{ __('Order channel webhooks') }}&nbsp;</h4><a href="{{ route('order_channels.recreateOrderChannelWebhooks', ['orderChannel' => $orderChannel]) }}" class="btn bg-logoOrange btn-sm borderOrange text-white font-weight-700">{{ __('Recreate all') }}</a>
            </div>
            @if(count($orderChannelInfo['data']['webhook_info']['webhooks']) > 0)
            <table class="table align-items-center table-flush items-table">
                <thead>
                    <tr class="text-black">
                        <th scope="col">{{ __('Topic') }}</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($orderChannelInfo['data']['webhook_info']['webhooks'] as $key => $webhook)
                    <tr>
                        <td class="py-1">{{ strtoupper($webhook['topic'] ?? '') }}</td>
                        <td class="py-1 text-right">
                            <form action="{{ route('order_channels.removeOrderChannelWebhook', ['orderChannel' => $orderChannel, 'id' => $webhook['id']]) }}" method="post" style="display: inline-block">
                                @csrf
                                <button type="button" class="table-icon-button" data-confirm-message="{{ __('Are you sure you want to delete this webhook?') }}" data-confirm-button-text="Delete">
                                    <i class="picon-trash-filled del_icon icon-lg" title="Delete"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    @endif
    <div class="d-flex text-center py-3 justify-content-between flex-column">
        <h4 class="text-left">{{ __('Packiyo webhooks') }}</h4>
        <table class="table align-items-center table-flush items-table">
            <thead>
                <tr class="text-black">
                    <th scope="col">{{ __('Name') }}</th>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="py-2">Shipment store</td>
                    <td class="py-2 text-right">
                    @if($webhook = $orderChannel->webhooks->where('object_type', \App\Models\Shipment::class)->where('operation', \App\Models\Webhook::OPERATION_TYPE_STORE)->first())
                        <form action="{{ route('order_channels.removePackiyoWebhook', ['orderChannel' => $orderChannel, 'webhook' => $webhook]) }}" method="post" style="display: inline-block">
                            @csrf
                            <button type="button" class="table-icon-button" data-confirm-message="{{ __('Are you sure you want to delete this webhook?') }}" data-confirm-button-text="Delete">
                                <i class="picon-trash-filled del_icon icon-lg" title="Delete"></i>
                            </button>
                        </form>
                    @else
                        <form action="{{ route('order_channels.createPackiyoWebhook', ['orderChannel' => $orderChannel, 'objectType' => \App\Models\Shipment::class, 'operation' => \App\Models\Webhook::OPERATION_TYPE_STORE]) }}" method="post" style="display: inline-block">
                            @csrf
                            <button type="submit" class="table-icon-button">
                                <i class="picon-add-circled-light icon-lg" title="Create"></i>
                            </button>
                        </form>
                    @endif
                    </td>
                </tr>
                <tr>
                    <td class="py-2">Inventory adjustment</td>
                    <td class="py-2 text-right">
                    @if($webhook = $orderChannel->webhooks->where('object_type', \App\Models\InventoryLog::class)->where('operation', \App\Models\Webhook::OPERATION_TYPE_STORE)->first())
                        <form action="{{ route('order_channels.removePackiyoWebhook', ['orderChannel' => $orderChannel, 'webhook' => $webhook]) }}" method="post" style="display: inline-block">
                            @csrf
                            <button type="button" class="table-icon-button" data-confirm-message="{{ __('Are you sure you want to delete this webhook?') }}" data-confirm-button-text="Delete">
                                <i class="picon-trash-filled del_icon icon-lg" title="Delete"></i>
                            </button>
                        </form>
                    @else
                        <form action="{{ route('order_channels.createPackiyoWebhook', ['orderChannel' => $orderChannel, 'objectType' => \App\Models\InventoryLog::class, 'operation' => \App\Models\Webhook::OPERATION_TYPE_STORE]) }}" method="post" style="display: inline-block">
                            @csrf
                            <button type="submit" class="table-icon-button">
                                <i class="picon-add-circled-light icon-lg" title="Create"></i>
                            </button>
                        </form>
                    @endif
                    </td>
                </tr>
                <tr>
                    <td class="py-2">Order Cancel</td>
                    <td class="py-2 text-right">
                        @if($webhook = $orderChannel->webhooks->where('object_type', \App\Models\Order::class)->where('operation', \App\Models\Webhook::OPERATION_TYPE_DESTROY)->first())
                            <form action="{{ route('order_channels.removePackiyoWebhook', ['orderChannel' => $orderChannel, 'webhook' => $webhook]) }}" method="post" style="display: inline-block">
                                @csrf
                                <button type="button" class="table-icon-button" data-confirm-message="{{ __('Are you sure you want to delete this webhook?') }}" data-confirm-button-text="Delete">
                                    <i class="picon-trash-filled del_icon icon-lg" title="Delete"></i>
                                </button>
                            </form>
                        @else
                            <form action="{{ route('order_channels.createPackiyoWebhook', ['orderChannel' => $orderChannel, 'objectType' => \App\Models\Order::class, 'operation' => \App\Models\Webhook::OPERATION_TYPE_DESTROY]) }}" method="post" style="display: inline-block">
                                @csrf
                                <button type="submit" class="table-icon-button">
                                    <i class="picon-add-circled-light icon-lg" title="Create"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

