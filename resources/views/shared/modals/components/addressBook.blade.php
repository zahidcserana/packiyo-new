<form
    method="post"
    action="{{ $addressBook ? route('address_book.update', ['address_book' => $addressBook]) : route('address_book.store') }}"
    autocomplete="off" class="address-book-form modal-content">
    @csrf
    @if ($addressBook) {{ method_field('PUT') }} @endif
    <div class="modal-header border-bottom mx-4 px-0">
        <h6 class="modal-title text-black text-left" id="modal-title-notification">{{ __(':action Address', ['action' => $addressBook ? 'Edit' : 'Add']) }}</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
            <span aria-hidden="true" class="text-black">&times;</span>
        </button>
    </div>
    <div class="modal-body py-3 overflow-auto">
        @if ($addressBook)
            <input type="hidden" name="id" value="{{ $addressBook->id }}"/>
            <input type="hidden" name="customer_id" value="{{ $addressBook->customer_id }}" />
        @else
            @include('shared.forms.customerSelect')
        @endif
        @include('shared.forms.input', [
            'name' => 'name',
            'label' => __('Address Label / Reference'),
            'error' => ! empty($errors->get('name')) ? $errors->first('name') : false,
            'value' => $addressBook->name ?? ''
        ])
        @include('shared.forms.contactInformationFields', [
            'name' => 'contact_information',
            'contactInformation' => $addressBook->contactInformation ?? ''
        ])
    </div>
    <div class="modal-footer">
        <button type="submit" class="btn bg-logoOrange text-white mx-auto px-5 font-weight-700 address-book">{{ __('Save') }}</button>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.address-book').click(function (e) {
            e.preventDefault();
            e.stopPropagation();
            let modal = $('#address-book-modal');

            $(document).find('.form-error-messages').remove()

            let _form = $(this).closest('.address-book-form');
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
                    modal.modal('toggle');
                    toastr.success(data.message)
                    window.dtInstances['#address-books-table'].ajax.reload()
                },
                error: function (response) {
                    appendValidationMessages(modal, response)
                }
            });
        });
    });
</script>
