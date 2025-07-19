<div class="table-responsive px-md-2 px-0 has-scrollbar items-table">
    <table class="col-12 table align-items-center table-small-paddings table-th-small-font table-td-small-font table-flush">
        <thead>
        <tr>
            <th scope="col">{{ __('Tote name') }}</th>
            <th scope="col">{{ __('Product') }}</th>
            <th scope="col">{{ __('Quantity picked') }}</th>
            <th scope="col">{{ __('Picked from') }}</th>
            <th scope="col">{{ __('Date') }}</th>
            <th scope="col">{{ __('Picked by') }}</th>
            <th scope="col">{{ __('Batch') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach( $order->orderItems as $orderItem )
            @foreach ($orderItem->toteOrderItems as $key => $toteOrderItem)
                <tr>
                    <td>
                        <a href="{{ route('tote.edit', $toteOrderItem->tote) }}" target="_blank">{{ $toteOrderItem->tote->name }}</a>
                    </td>
                    <td>
                        {{ __('SKU') }}: {{ $toteOrderItem->orderItem->sku }} <br>
                        {{ __('Name') }}: <a href="{{ route('product.edit', $toteOrderItem->orderItem->product) }}" target="_blank">{{ $toteOrderItem->orderItem->product->name }}</a>
                    </td>
                    <td>
                        {{ $toteOrderItem->quantity }}
                    </td>
                    <td>
                        {{ $toteOrderItem->location->name ?? '' }}
                    </td>
                    <td>
                        {{ user_date_time($toteOrderItem->created_at, true) }}
                    </td>
                    <td>
                        {{ $toteOrderItem->user->contactInformation->name ?? '' }}
                    </td>
                    <td>
                        @if ($toteOrderItem->pickingBatchItem)
                            <a href="{{ route('picking_batch.getItems', ['pickingBatch' => $toteOrderItem->pickingBatchItem->picking_batch_id]) }}" target="_blank">
                                {{ $toteOrderItem->pickingBatchItem->picking_batch_id }}
                            </a>
                        @endif
                    </td>
                </tr>
            @endforeach
        @endforeach
        </tbody>
    </table>
</div>
