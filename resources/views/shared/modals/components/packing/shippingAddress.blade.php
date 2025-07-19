<div class="modal fade confirm-dialog" id="shippingInformationEdit" role="dialog" {{ $dataKeyboard ?? '' ? 'data-backdrop=static data-keyboard=false' : '' }}>
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content bg-white">
            <div class="modal-header border-bottom">
                <h6 class="modal-title">{{ __('Shipping Address') }}<br><span class="font-xs font-weight-400">{{ __('This is where the order will be shipped to.') }}</span></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @csrf
                <input type="hidden" name="customer_id" value="{{ $order->customer->id }}" class="customer_id" />
                @include('shared.forms.contactInformationFields', [
                    'name' => 'shipping_contact_information',
                    'contactInformation' => $order->shippingContactInformation ?? ''
                ])
            </div>
            <div class="modal-footer border-top d-flex justify-content-center">
                <button data-dismiss="modal" class="btn mx-2">{{ __('Cancel') }}</button>
                <button type="button" data-dismiss="modal" data-toggle="modal" class="btn bg-blue text-white mx-2 shipping_contact_info_set">{{ __('Save') }}</button>
            </div>
        </div>
    </div>
</div>
