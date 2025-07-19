<div class="modal fade" id="add-hoc-modal" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="{{route('bulk_invoice_batches.invoices.ad_hoc', ['bulk_invoice_batch' => $batch->id])}}">
                @csrf
                <div class="modal-header px-0">
                    <div class="mx-4 pb-4 d-flex w-100 border-bottom-gray">
                        <h6 class="modal-title text-black text-left"
                            id="modal-title-notification">{{ __('New ad hoc charge') }}</h6>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                            <span aria-hidden="true" class="text-black">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body py-0">
                    <fieldset>
                        <div class="form-group">
                            <label for="rate_card_id" class="form-control-label">{{ __('Select the Rate Card') }}</label>
                            <select name="rate_card_id" class="form-control" id="rate_card_id">
                                <option></option>
                                @foreach($data['rateCards'] as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="select-tags form-group">
                            <label for="customers_ids" class="form-control-label">{{ __('Select customers') }}</label>
                            <input type="hidden" name="customers" value="">
                            <select name="customers_ids[]" id="customers_ids">
                                <option value="all">{{ __('All Clients') }}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label">
                                {{ __('Rate') }}
                            </label>
                            <select
                                name="billing_rate_id"
                                class="form-control"
                                id="billing_rate_id"
                            >
                                <option></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-control-label" >{{ __('Quantity') }}</label>
                            <input
                                type="text"
                                name="quantity"
                                class="form-control"
                                placeholder="{{ __('Quantity') }}"
                                value=""
                                autocomplete="off"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-control-label" >{{ __('Date') }}</label>
                            <input class="form-control dt-daterangepicker" type="text" name="period_end" autocomplete="false">
                        </div>
                    </fieldset>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
    <script>
        $(document).ready(function () {
            dtDateRangePicker();

            let currentCustomers = [];

            const rateCardSelect = $('#rate_card_id')
            const customersSelect = $('#customers_ids')
            const rateSelect = $('#billing_rate_id')

            rateCardSelect.select2({
                placeholder: "Select",
                allowClear: true
            });

            customersSelect.select2({
                multiple: "true",
                placeholder: '{{ __('Search') }}',
            });
            customersSelect.val(null).trigger('change');

            rateSelect.select2({
                placeholder: "Select",
            });

            rateCardSelect.on('select2:select', function (e) {
                const { id: rateCardId } = e.params.data;

                $.ajax({
                    type: 'GET',
                    url: `rate_cards/${rateCardId}/customers`
                }).then(function (data) {
                    const { data: { customers, charges } } = data;

                    currentCustomers = customers;

                    customersSelect.empty();

                    customers.forEach(customerOption => {
                        customersSelect.append(new Option(customerOption.name, customerOption.id, false, false)).trigger('change');
                    });

                    customersSelect.prepend(new Option('All Clients', 'all', false, false)).trigger('change');

                    customersSelect.trigger({
                        type: 'select2:select',
                        params: {
                            data: customersSelect
                        }
                    });

                    customersSelect.val(null).trigger('change');

                    rateSelect.empty();

                    charges.forEach(chargeOption => {
                        rateSelect.append(new Option(chargeOption.name, chargeOption.id, false, false)).trigger('change');
                    });

                    rateSelect.trigger({
                        type: 'select2:select',
                        params: {
                            data: charges
                        }
                    });

                    rateSelect.val(null).trigger('change');
                });
            });

            customersSelect.on('select2:select', function (e) {
                const data = e.params.data;
                if (data.id === 'all') {
                    const currentCustomersIds = currentCustomers.map(item => item.id);
                    currentCustomersIds.unshift('all');
                    console.log(currentCustomersIds);
                    customersSelect.val(currentCustomersIds).trigger('change');
                }
            });

            customersSelect.on('select2:unselect', function (e) {
                const currentSelectedValues = customersSelect.select2('data').map(item => item.id);
                customersSelect.val(currentSelectedValues.filter(value => value !== 'all')).trigger('change');
            });
        })
    </script>
@endpush
