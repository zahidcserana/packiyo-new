@component('mail::message', ['headerImage' => $headerImage])

{{ __('This is an automated email.') }}<br>

{{ __('Order No :number has been shipped', ['number' => $shipment->order->number]) }}

{{ __('Shipping Carrier: :carrier', ['carrier' => $shipment->shippingMethod->shippingCarrier->name ?? '']) }}<br>
{{ __('Shipping Method: :method', ['method' => $shipment->shippingMethod->name ?? '']) }}<br>
{{ __('Tracking No: :tracking', ['tracking' => $shipment->trackingNumbers()]) }}

@endcomponent
