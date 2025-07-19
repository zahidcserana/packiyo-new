<div class="sidebar">
    <a class="close-sidebar-button">&#10005;</a>
    <h3 class="card-title">{{ __('Last 10 Shipments') }}</h3>
    <ul class="list-group">
        @if(!count($shipments))
            <li class="list-group-item text-center">
                {{ __('No shipments to show.') }}
            </li>
        @endif
        @foreach($shipments as $shipment)
            <li class="list-group-item">
                <a href="{{ route('shipment.getPackingSlip', $shipment) }}" target="_blank" class="btn btn-sm btn-secondary reprint-packing-slip-button">{{ __('Packing slip') }}</a>
                <div class="d-flex"><div>{{ __('Date:') }} <span class="font-weight-600 font-xs text-neutral-text-gray text-break-all">{{ user_date_time($shipment->created_at, true) }}</span></div></div>
                <div class="d-flex"><div>{{ __('Order:') }} <a target="_blank" href="/order/{{$shipment->order->id}}/edit" class="font-weight-600 font-xs text-neutral-text-gray text-break-all">{{$shipment->order->number}}</a></div></div>
                @if ($shipment->shippingMethod)
                    <div class="d-flex"><div>{{ __('Shipping:') }} <span class="font-weight-600 font-xs text-neutral-text-gray text-break-all">{{ $shipment->shippingMethod->carrierNameAndName }}</span></div></div>
                @endif
                @if (!is_null($shipment->shipmentTrackings))
                    @foreach($shipment->shipmentTrackings as $tracking)
                        <div class="d-flex justify-content-between">
                            <div>{{ __('Tracking:') }} <strong class="text-break-all"><a href="{{ $tracking->tracking_url }}" target="_blank" class="font-weight-600 font-xs text-neutral-text-gray text-break-all">{{ $tracking->tracking_number }}</a></strong></div>
                        </div>
                    @endforeach
                @endif
                @if (!is_null($shipment->shipmentLabels))
                    @foreach ($shipment->shipmentLabels as $key => $shipmentLabel)
                        <a target="_blank" href="{{ route('shipment.label', ['shipment' => $shipment, 'shipmentLabel' => $shipmentLabel]) }}" class="reprint-label-button text-primary"><strong>{{ __('Label :number', ['number' => $key + 1]) }}</strong></a>
                    @endforeach
                @endif
            </li>
        @endforeach
    </ul>
</div>
