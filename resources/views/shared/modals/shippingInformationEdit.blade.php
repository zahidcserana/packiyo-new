<div class="modal fade confirm-dialog" id="shippingInformationEdit" role="dialog" {{ $dataKeyboard ?? '' ? 'data-backdrop=static data-keyboard=false' : '' }}>
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content productForm">
            <div class="modal-header px-0">
                <div class="mx-4 pb-4 d-flex w-100 border-bottom-gray">
                    <h6 class="modal-title text-black text-left"
                        id="modal-title-notification">{{ __('Edit shipping information') }}</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                        <span aria-hidden="true" class="text-black">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body text-white pb-3 pt-0 overflow-auto">
                @csrf
                <input type="hidden" name="customer_id" value="{{ $order->customer->id }}" class="customer_id" />
                @include('shared.forms.contactInformationFields', [
                    'name' => 'shipping_contact_information',
                    'contactInformation' => $order->shippingContactInformation ?? ''
                ])
                <button type="button" data-dismiss="modal" data-toggle="modal" class="btn bg-logoOrange text-white mx-auto px-5 font-weight-700 confirm-button mt-5 shipping_contact_info_set {{ $saveBtnClass ?? '' }}">
                    {{ __('Save') }}
                </button>
            </div>
        </div>
    </div>
</div>
