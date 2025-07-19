<form
    class="locationForm modal-content"
    method="POST"
    @if ($location)
        action="{{ route('location.update', $location) }}"
    @else
        action="{{ route('location.store') }}"
    @endif
    autocomplete="off"
    data-location-modal-route="{{ route('location.getLocationModal', $location) }}"
>
    @csrf

    @if ($location)
        @method('PUT')
    @endif

    <div class="modal-header border-bottom mx-4 px-0">
        <h6 class="modal-title text-black text-left" id="modal-title-notification">
            @if ($location)
                {{ __('Edit location') }}
            @else
                {{ __('Create location') }}
            @endif
        </h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
            <span aria-hidden="true" class="text-black">&times;</span>
        </button>
    </div>

    <div class="modal-body py-3 overflow-auto" id="modalBody">
        <div class="row inputs-container">
            @if(!isset($sessionCustomer) && !isset($location->warehouse->customer_id))
                <x-forms.inputs.select-ajax
                    name="customer_id"
                    placeholder="{{ __('Select customer') }}"
                    label="{{ __('Customer') }}"
                    container-class="col-lg-6"
                    url="{{ route('user.getCustomers') }}"
                />
            @else
                <input
                    type="hidden"
                    name="customer_id"
                    value="{{ $location->warehouse->customer_id ?? $sessionCustomer->id ?? '' }}"
                    class="customer_id"
                />
            @endif

            @if (! $location)
                <x-forms.inputs.select-ajax
                    name="warehouse_id"
                    placeholder="{{ __('Select a warehouse') }}"
                    label="{{ __('Warehouse') }}"
                    container-class="col-lg-6"
                    url="{{ route('purchase_order.filterWarehouses', $sessionCustomer->id ?? '') }}"
                />
            @endif

            <x-forms.inputs.input
                name="name"
                placeholder="{{ __('Name') }}"
                value="{{ $location->name ?? '' }}"
                label="{{ __('Name') }}"
                container-class="col-lg-6"
            />

            <x-forms.inputs.select-ajax
                name="location_type_id"
                placeholder="{{ __('Choose a location type') }}"
                :value="[
                    'id' => $location->location_type_id ?? '',
                    'text' => $location->locationType->name ?? '',
                ]"
                label="{{ __('Location Type') }}"
                container-class="col-lg-6"
                url="{{ route('location.types', $sessionCustomer ?? '') }}"
            />

            <div class="col-lg-12 form-inline">
                <x-forms.inputs.checkbox
                    name="pickable"
                    label="{{ __('Pickable') }}"
                    checked="{{ $location->pickable ?? '' }}"
                    container-class="mr-4"
                />

                <x-forms.inputs.checkbox
                        name="sellable"
                        label="{{ __('Sellable') }}"
                        checked="{{ $location->sellable ?? '' }}"
                        container-class="mr-4"
                />

                <x-forms.inputs.checkbox
                    name="bulk_ship_pickable"
                    label="{{ __('Bulk ship pickable') }}"
                    checked="{{ $location->bulk_ship_pickable ?? '' }}"
                    container-class="mr-4"
                />

                <x-forms.inputs.checkbox
                    name="disabled_on_picking_app"
                    label="{{ __('Disabled on picking app') }}"
                    checked="{{ $location->disabled_on_picking_app ?? '' }}"
                    container-class="mr-4"
                />

                <x-forms.inputs.checkbox
                    name="is_receiving"
                    label="{{ __('Allow multiple lots') }}"
                    checked="{{ $location->is_receiving ?? '' }}"
                    container-class="mr-4"
                />
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button
            type="submit"
            id="submit-button"
            class="btn bg-logoOrange text-white mx-auto px-5 font-weight-700 modal-submit-button"
        >
            {{ __('Save') }}
        </button>
    </div>
</form>
<script>
    $(document).ready(function() {
        let customerSelect = $('select[name="customer_id"]')
        let warehouseSelect = $('select[name="warehouse_id"]')
        let locationTypeSelect = $('select[name="location_type_id"]')

        customerSelect.select2({
            dropdownParent: customerSelect.parents('.modal')
        })

        warehouseSelect.select2({
            dropdownParent: warehouseSelect.parents('.modal')
        })

        locationTypeSelect.select2({
            dropdownParent: locationTypeSelect.parents('.modal')
        })

        customerSelect.on('change', function () {
            let customerId = customerSelect.val()

            warehouseSelect.empty()
            locationTypeSelect.empty()

            if (customerId) {
                warehouseSelect.select2({
                    ajax: {
                        url: "purchase_orders/filterWarehouses/" + customerId
                    }
                })

                locationTypeSelect.select2({
                    ajax: {
                        url: "location/types/filter/" + customerId
                    }
                })
            }
        }).trigger('change')

        $('.modal-submit-button').click(function (e) {
            e.preventDefault()
            e.stopPropagation()

            let modal = $(this).parents('.modal')
            let form = $(this).closest('.locationForm')
            let formData = new FormData(form[0])

            $.ajax({
                type: 'POST',
                url: form.attr('action'),
                data: formData,
                processData: false,
                contentType: false,
                success: function (data) {
                    modal.modal('toggle')

                    toastr.success(data.message)

                    dtInstances['#locations-table'].ajax.reload()
                },
                error: function (response) {
                    appendValidationMessages(modal, response)
                }
            })
        })
    })
</script>
