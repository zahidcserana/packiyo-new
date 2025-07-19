<form method="post" action="{{ route('shipment.tracking', [ 'shipment' => $shipment ]) }}" autocomplete="off" data-type="POST" class="modal-content shipment-tracking-form">
    @csrf
    <div class="modal-header px-0">
        <div class="mx-4 pb-4 d-flex w-100 border-bottom-gray">
            <h6 class="modal-title text-black text-left" id="modal-title-notification">{{ __(':action Tracking', ['action' => empty($shipmentTracking->id) ? 'Add' : 'Update']) }}</h6>
            <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                <span aria-hidden="true" class="text-black">&times;</span>
            </button>
        </div>
    </div>
    <div class="modal-body text-center py-3 overflow-auto">
        <div class="form-group row">
            <label for="shipping-method-name" class="col-sm-2 col-form-label text-left">{{ __('Shipping method') }}</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" id="shipping-method-name" placeholder="Shipping method name" name="shipping_method_name" value="{{ $shipment->shippingMethod->name ?? $shipment->shipping_method_name ?? 'Generic' }}">
            </div>
        </div>
        <div class="form-group row">
            <label for="tracking-number" class="col-sm-2 col-form-label text-left">{{ __('Tracking number') }}</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" id="tracking-number" placeholder="Tracking number" name="tracking_number" value="{{ $shipmentTracking->tracking_number ?? '' }}">
            </div>
        </div>
        <div class="form-group row">
            <label for="tracking-url" class="col-sm-2 col-form-label text-left">{{ __('Tracking link') }}</label>
            <div class="col-sm-10">
                <input type="text" class="form-control" id="tracking-url" placeholder="Tracking url" name="tracking_url" value="{{ $shipmentTracking->tracking_url ?? '' }}">
            </div>
        </div>
        <input type="hidden" value="{{ $shipment->id }}" name="shipment_id">
        <input type="hidden" value="{{ $shipmentTracking->id ?? null }}" name="id">
        <input type="hidden" value="{{ \App\Models\ShipmentTracking::TYPE_SHIPPING }}" name="type">
    </div>
    <div class="modal-footer">
        <button type="submit" class="btn bg-logoOrange text-white mx-auto px-5 font-weight-700 shipment-tracking-submit">{{ __('Save') }}</button>
    </div>
</form>