@if ($order->shipments->count() > 0)
    @foreach ($order->shipments as $shipment)
        <div class="shipment-box p-2 mb-4">
            <div class="shipment-list"
                 data-title="{{ __('Items in this shipment') }}"
                 @if ($shipment->shipmentItems->count() > 0)
                     data-load-popover=".shipment-items-{{ $shipment->id }}"
                 @endif
                 data-original-title=""
                 title="">
                @if (!is_null($shipment->voided_at))
                    <span class="d-block font-xs mb-1 text-red"><strong>{{ __('VOIDED') }}</strong></span>
                @endif
                <div class="d-block font-xs mb-1">{{ __('Date:') }} <strong>{{ user_date_time($shipment->created_at, true) }}</strong></div>
                <div class="d-block font-xs mb-1">{{ __('Shipped By:') }} {{ $shipment->user->contactInformation->name ?? '' }}</div>
                <div class="d-block font-xs mb-1">{{ __('Shipping Method:') }} {{ $shipment->shippingMethod->name ?? $shipment->shipping_method_name ?? __('Generic') }}</div>

                @if (!is_null($shipment->shipmentTrackings))
                    @foreach($shipment->shipmentTrackings as $tracking)
                        <div class="d-block font-xs mb-1">
                            {{ $tracking->type == \App\Models\ShipmentTracking::TYPE_RETURN ? __('Return Tracking Number:') : __('Tracking Number:') }}
                            <a href="{{ $tracking->tracking_url }}" target="_blank" class="font-weight-600 font-xs text-neutral-text-gray">{{ $tracking->tracking_number }}</a>
                            <button type="button" class="table-icon-button" data-shipment-id="{{ $shipment->id }}" data-id="{{ $tracking->id }}" data-toggle="modal" data-target="#shipment-tracking-modal">
                                <i class="picon-edit-light icon-sm" title="{{ __('Edit') }}"></i>
                            </button>
                        </div>
                    @endforeach
                @endif

                @if (!$shipment->shipping_method_id && $shipment->shipmentTrackings()->doesntExist())
                    <a  href="#" data-shipment-id="{{ $shipment->id }}" data-id="{{ null }}" data-toggle="modal" data-target="#shipment-tracking-modal">{{ __('Add Tracking Number') }}</a>
                @endif

                <div class="d-block font-xs mb-1">{{ __('Quantity Shipped:') }} {{ $shipment->shipmentItems->sum('quantity') . ' ' . __('of') . ' ' . $order->orderItems->sum('quantity') }}</div>
                <div class="d-block actions">
                    @if (!is_null($shipment->shipmentLabels))
                        @php $labelNumber = 0; @endphp
                        @foreach ($shipment->shipmentLabels as $shipmentLabel)
                            <a href="{{ route('shipment.label', ['shipment' => $shipment, 'shipmentLabel' => $shipmentLabel]) }}"
                               title="{{ __('View label') }}"
                               target="_blank"
                               class="btn bg-logoOrange text-white my-2 px-3 py-2 font-weight-700 border-8">
                                @if($shipmentLabel->type === \App\Models\ShipmentLabel::TYPE_RETURN)
                                    {{ __('Return label :number', ['number' => $labelNumber]) }}
                                @else
                                    {{ __('Label :number', ['number' => ++$labelNumber]) }}
                                @endif
                            </a>
                        @endforeach
                        @if (is_null($shipment->voided_at) && allow_void_label())
                            <a href="#"
                               data-route="{{ route('shipments.void', ['shipment' => $shipment]) }}"
                               class="confirmation btn bg-red text-white my-2 px-3 py-2 font-weight-700 border-8">
                                {{ __('Void Label') }}
                            </a>
                        @endif
                        <a href="{{ route('shipment.getPackingSlip', $shipment) }}" target="_blank"
                           class="btn bg-logoOrange text-white my-2 px-3 py-2 font-weight-700 border-8">{{ __('Packing slip') }}</a>

                    @endif
                    @foreach($shipment->packages as $package)
                        @foreach($package->documents as $packageDocument)
                            <a
                                href="{{ route('shipment.package_document', ['shipment' => $shipment, 'packageDocument' => $packageDocument]) }}"
                                target="_blank"
                                class="btn bg-logoOrange text-white my-2 px-3 py-2 font-weight-700 border-8"
                            >{{ Str::headline($packageDocument->type) }} @if($packageDocument->submitted_electronically){{ __('ETD submitted') }}@endif</a>
                        @endforeach
                    @endforeach
                    @if($ediPackingLabels[$shipment->id] ?? false)
                        <a
                            href="{{ $ediPackingLabels[$shipment->id]['url'] }}"
                            target="_blank"
                            class="btn bg-logoOrange text-white my-2 px-3 py-2 font-weight-700 border-8"
                        >{{ $ediPackingLabels[$shipment->id]['name'] }}</a>
                    @endif
                    @foreach($shipment->links as $link)
                        <a
                            href="{{ $link->url }}"
                            target="_blank"
                            class="btn bg-logoOrange text-white my-2 px-3 py-2 font-weight-700 border-8" rel="noopener noreferrer"
                        ><i class="picon-link-light icon-lg text-white" title="Edit"></i> {{ $link->name }}</a>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="shipment-items-{{ $shipment->id }} d-none">
            <div class="w-100 table-responsive px-0 has-scrollbar items-table">
                <table class="col-12 table table-borderless table-small-paddings table-th-small-font table-td-small-font table-flush">
                    <thead>
                    <tr>
                        <th>{{ __('Qty') }}</th>
                        <th>{{ __('Sku') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Lots') }}</th>
                        <th>{{ __('Serial number') }}</th>
                        <th>{{ __('Location') }}</th>
                        <th>{{ __('Tote') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($shipment->shipmentItems as $shipmentItem)
                        <tr>
                            <td>{{ $shipmentItem->quantity }}</td>
                            <td>{{ $shipmentItem->orderItem->sku }}</td>
                            <td>{{ $shipmentItem->orderItem->name }}</td>
                            <td>{{ $shipmentItemLots[$shipmentItem->id] }}</td>
                            <td>{{ $shipmentItemSerialNumbers[$shipmentItem->id] }}</td>
                            <td>{{ $shipmentItemLocations[$shipmentItem->id] }}</td>
                            <td>{{ $shipmentItemTotes[$shipmentItem->id] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@else
    <p class="p-2">{{ __('No records found.') }}</p>
@endif
