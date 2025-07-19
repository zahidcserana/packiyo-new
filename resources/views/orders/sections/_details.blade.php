<table class="table table-sm table-borderless table-layout-fixed table-order table-details font-sm">
    <tbody>
        <tr>
            <td class="text-break align-middle">{{ __('Order #') }}</td>
            <td class="text-right">{{ $order->number }}</td>
        </tr>
        @if(auth()->user()->isAdmin() && $order->order_channel_payload)
        <tr>
            <td class="text-break align-middle">{{ __('Order channel payload') }}</td>
            <td class="text-right"><a href="{{ route('order.order_channel_payload', ['order' => $order]) }}" target="_blank">{{ __('Show') }}</a></td>
        </tr>
        @endif
        <tr>
            <td class="text-break align-middle">{{ __('Order channel') }}</td>
            <td class="text-right">{{ $order->orderChannel->name ?? __('Manual order') }}</td>
        </tr>
        <tr>
            <td class="text-break align-middle">{{ __('Order Type') }}</td>
            <td class="text-right">{{ \App\Models\Order::ORDER_TYPES[$order->orderType()] }}</td>
        </tr>
        <tr>
            <td class="text-break align-middle">{{ __('Order date') }}</td>
            <td class="text-right">{{ user_date_time($order->ordered_at, true) }}</td>
        </tr>
        <tr>
            <td class="text-break align-middle">{{ __('Currency') }}</td>
            <td class="text-right">{{ $currency }}</td>
        </tr>
        @if ($order->isTransferOrder())
        <tr>
            <td class="text-break align-middle">{{ __('This is a transfer order') }}</td>
            <td class="text-right"><a href="{{ route('purchase_orders.edit', [$order->purchaseOrder]) }}">{{ __('related PO') }}</a></td>
        </tr>
        @endif
        <tr>
            <td class="text-break align-middle">{{ __('Status') }}</td>
            <td class="text-right">
                @if($order->getStatusText() === \App\Models\Order::STATUS_FULFILLED)
                    <span>{{ __(\App\Models\Order::STATUS_FULFILLED) }}</span>
                @elseif($order->getStatusText() === \App\Models\Order::STATUS_CANCELLED)
                    <span>{{ __(\App\Models\Order::STATUS_CANCELLED) }}</span>
                @else
                    @include('shared.forms.select', [
                        'name' => 'order_status_id',
                        'label' => '',
                        'containerClass' => 'select-scrollable',
                        'placeholder' => __('Select an order status'),
                        'error' => !empty($errors->get('order_status_id')) ? $errors->first('order_status_id') : false,
                        'value' => $order->order_status_id ?? 'pending',
                        'options' => $orderStatuses->pluck('name', 'id')
                    ])
                @endif
            </td>
        </tr>
        <tr>
            <td class="p-4"></td>
            <td class="p-4"></td>
        </tr>
        @if ($order->ready_to_ship && $order->ready_to_pick)
            <tr>
                <td class="text-word-break align-middle" colspan="2">{{ __('This order is ready to ship') }}</td>
            </tr>
        @elseif($order->is_archived)
            <tr>
                <td class="text-word-break align-middle" colspan="2">{{ __('This order is archived') }}</td>
            </tr>
        @endif
        @if ($order->orderLock)
            <tr>
                <td class="text-word-break align-middle">{{ __('Locked by :name', ['name' => $order->orderLock->user->contactInformation->name]) }}</td>
                <td class="text-right">
                    @if(auth()->user()->isAdmin())
                        <button
                            data-confirm-title="{{ __('Remove lock') }}"
                            data-confirm-message="{{ __('Are you sure you want to remove lock on this order?') }}"
                            form="order-unlock-form"
                            type="button"
                            class="btn bg-logoOrange text-white mx-auto px-3 py-2 font-weight-700 border-8">
                            {{ __('Remove lock') }}
                        </button>
                    @endif
                </td>
            </tr>
        @elseif(!($order->ready_to_ship && $order->ready_to_pick) && !($order->fulfilled_at || $order->cancelled_at))
            <tr>
                @if(count($order->notReadyToShipExplanation()) > 0)
                    <td class="text-word-break align-middle" colspan="2">
                        {{ __('This order is not ready to ship because:') }}
                        <ul>
                            @foreach($order->notReadyToShipExplanation() as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </td>
                @elseif(!is_null($order->notReadyToPickExplanation()))
                        <td class="text-word-break align-middle" colspan="2">
                            {{ __('This order is ready to ship, but not ready to pick because:') }}
                            <ul>
                                <li>{{ $order->notReadyToPickExplanation() }}</li>
                            </ul>
                        </td>
                @endif
            </tr>
        @endif
        @if(!$order->has_holds)
            <tr>
                <td class="text-word-break align-middle" colspan="2">{{ __('No holds on this order') }}</td>
            </tr>
        @endif
        <tr>
            <td class="text-word-break align-middle">{{ __('Hold Until:') }}</td>
            <td>
                @include('shared.forms.input', [
                    'containerClass' => 'd-flex justify-content-end',
                    'name' => 'hold_until',
                    'label' => '',
                    'error' => ! empty($errors->get('hold_until')) ? $errors->first('hold_until') : false,
                    'value' => !empty($order->hold_until) ? user_date_time($order->hold_until) :  '',
                    'class' => 'dt-daterangepicker text-right without-autofill'
                ])
            </td>
        </tr>
        <tr>
            <td class="text-word-break align-middle">{{ __('Required ship date:') }}</td>
            <td>
                @include('shared.forms.input', [
                    'containerClass' => 'd-flex justify-content-end',
                    'name' => 'ship_before',
                    'label' => '',
                    'error' => ! empty($errors->get('ship_before')) ? $errors->first('ship_before') : false,
                    'value' => !empty($order->ship_before) ? user_date_time($order->ship_before) :  '',
                    'class' => 'dt-daterangepicker text-right without-autofill'
                ])
            </td>
        </tr>
        <tr>
            <td class="text-word-break align-middle">{{ __('Scheduled delivery:') }}</td>
            <td>
                @include('shared.forms.input', [
                    'containerClass' => 'd-flex justify-content-end',
                    'name' => 'scheduled_delivery',
                    'label' => '',
                    'error' => ! empty($errors->get('scheduled_delivery')) ? $errors->first('scheduled_delivery') : false,
                    'value' => !empty($order->scheduled_delivery) ? user_date_time($order->scheduled_delivery) :  '',
                    'class' => 'dt-daterangepicker text-right without-autofill'
                ])
            </td>
        </tr>
        <tr>
            <td class="text-word-break align-middle">{{ __('Warehouse:') }}</td>
            <td>
                @include('shared.forms.select', [
                    'name' => 'warehouse_id',
                    'label' => '',
                    'className' => 'warehouse_id',
                    'placeholder' => __('Select warehouse'),
                    'error' => !empty($errors->get('warehouse_id')) ? $errors->first('warehouse_id') : false,
                    'value' => $order->warehouse_id ?? old('warehouse_id'),
                    'options' => ['' => __('')] + $warehouses->pluck('contactInformation.name', 'id')->toArray()
                ])
            </td>
        </tr>
        <tr>
            <td class="text-word-break align-middle">{{ __('Delivery confirmation:') }}</td>
            <td>
                @include('shared.forms.select', [
                    'name' => 'delivery_confirmation',
                    'label' => '',
                    'className' => 'delivery_confirmation',
                    'placeholder' => __('Delivery confirmation'),
                    'error' => !empty($errors->get('delivery_confirmation')) ? $errors->first('delivery_confirmation') : false,
                    'value' => $order->delivery_confirmation,
                    'options' => [
                        '0' => __('Not set'),
                        \App\Models\Order::DELIVERY_CONFIRMATION_SIGNATURE => __('Signature'),
                        \App\Models\Order::DELIVERY_CONFIRMATION_ADULT_SIGNATURE => __('Adult signature'),
                        \App\Models\Order::DELIVERY_CONFIRMATION_NO_SIGNATURE => __('No signature')
                    ]
                ])
            </td>
        </tr>
        <tr>
        <td class="text-word-break align-middle">{{ __('Incoterms:') }}</td>
            <td>
                @include('shared.forms.select', [
                    'name' => 'incoterms',
                    'label' => '',
                    'className' => 'incoterms',
                    'error' => !empty($errors->get('incoterms')) ? $errors->first('incoterms') : false,
                    'value' => $order->incoterms,
                    'options' => [
                        '0' => __('Not set'),
                        \App\Models\Order::INCOTERMS_DDP => __('DDP'),
                        \App\Models\Order::INCOTERMS_DDU => __('DDU')
                    ]
                ])
            </td>
        </tr>
        <tr>
            <td class="text-word-break align-middle">{{ __('Handling instructions:') }}</td>
            <td>
                @include('shared.forms.input', [
                    'containerClass' => 'd-flex justify-content-end',
                    'name' => 'handling_instructions',
                    'label' => '',
                    'error' => !empty($errors->get('handling_instructions')) ? $errors->first('handling_instructions') : false,
                    'value' => $order->handling_instructions ?? ''
                    ])
            </td>
        </tr>
        @if($order->custom_invoice_url)
            <tr>
                <td></td>
                <td class="text-right">
                    <a href="{{ $order->custom_invoice_url }}" target="_blank" class="btn bg-logoOrange text-white mx-auto px-3 py-2 font-weight-700 border-8">
                        {{ __('Invoice') }}
                    </a>
                </td>
            </tr>
        @endif
    </tbody>
</table>

@include('shared.forms.typeCheckbox', [
    'name' => 'saturday_delivery',
    'label' => __('Saturday delivery'),
    'checked' => (! empty(old('saturday_delivery'))) ? old('saturday_delivery') :  $order->saturday_delivery,
    'checkboxFirst' => false,
    'containerClass' => 'd-flex justify-content-between px-2 p-2 font-sm',
    'labelClass' => 'w-100',
    'inputClass' => ''
])
