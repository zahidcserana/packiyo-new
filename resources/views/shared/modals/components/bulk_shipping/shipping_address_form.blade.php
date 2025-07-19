<div class="modal-header px-0">
    <div class="mx-4 pb-4 d-flex w-100 border-bottom-gray">
        <h6 class="modal-title text-black text-left"
            id="modal-title-notification">{{ __('Edit shipping information for Order: ') . $order->number }}</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
            <span aria-hidden="true" class="text-black">&times;</span>
        </button>
    </div>
</div>
<form
    method="post"
    action="{{ route('order.update', compact('order')) }}"
    autocomplete="off"
    class="shipping-information-edit-form">
    @csrf
    {{ method_field('PUT') }}
    <div class="modal-body text-center py-3 overflow-auto">
        <input type="hidden" name="customer_id" value="{{ $order->customer->id }}" class="customer_id" />
        @include('shared.forms.contactInformationFields', [
            'name' => 'shipping_contact_information',
            'contactInformation' => $order->shippingContactInformation ?? ''
        ])
    </div>
    <div class="modal-footer">
        <button type="submit"
                class="btn bg-logoOrange text-white mx-auto px-5 font-weight-700 modal-submit-button"
                id="submit-button">
            {{ __('Save') }}
        </button>
    </div>
</form>

<script>
    $(document).ready(function() {
        let countrySelect = $("[name='shipping_contact_information[country_id]']")

        countrySelect.select2({
            dropdownParent: $('#order-bulk-ship-shipping-information-edit')
        })

        $('.modal-submit-button').click(function (e) {
            e.preventDefault()
            e.stopPropagation()

            $(document).find('.form-error-messages').remove()

            let _form = $(this).closest('.shipping-information-edit-form');
            let form = _form[0];
            let formData = new FormData(form);

            $.ajax({
                type: 'POST',
                url: _form.attr('action'),
                headers: {'X-CSRF-TOKEN': formData.get('_token')},
                data: formData,
                processData: false,
                contentType: false,
                success: function (data) {
                    $('#order-bulk-ship-shipping-information-edit').modal('toggle');

                    toastr.success(data.message)
                },
                error: function (response) {
                    appendValidationMessages(
                        $('#order-bulk-ship-shipping-information-edit'),
                        response
                    )
                }
            });
        });
    })
</script>
