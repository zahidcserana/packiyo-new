<div class="modal fade" id="batch-invoices-modal" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="{{route('bulk_invoice_batches.store')}}">
                @csrf
                <div class="modal-header px-0">
                    <div class="mx-4 pb-4 d-flex w-100 border-bottom-gray">
                        <h6 class="modal-title text-black text-left" id="modal-title-notification">
                            {{ __('New invoice') }}
                        </h6>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                            <span aria-hidden="true" class="text-black">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body py-0">
                    <div class="form-group col-12">
                        <label for="" class="font-xs">{{ __('Start Date') }}</label>
                        <input class="form-control dt-daterangepicker" type="text" value="{{ user_date_time(now()) }}" name="start_date">
                    </div>
                    <div class="form-group col-12">
                        <label for="end_date" class="font-xs">{{ __('End Date') }}</label>
                        <input class="form-control dt-daterangepicker" type="text" name="end_date">
                    </div>
                    <div class="select-tags form-group col-12">
                        <label
                            for="customers-select"
                            class="font-xs" data-id="customers"
                        >
                            {{ __('Select Clients') }}
                        </label>
                        <input type="hidden" name="customers" value="">
                        <select name="customer_ids[]" id="customers-select">
                            <option value="all">{{ __('All') }}</option>
                            @foreach($customers as $id => $customerName)
                                <option value="{{ $id }}">{{ $customerName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <fieldset class="form-group col-12">
                        <legend class="font-xs">{{ __('How do you want to generate this?') }}</legend>

                        <div>
                            <input type="radio" id="multiple" name="type" value="individual" checked/>
                            <label for="multiple">{{ __('Generate an invoice for each selected customer') }} </label>
                        </div>

                        <div>
                            <input type="radio" id="single" name="type" value="aggregated"/>
                            <label for="single">{{ __('Generate a single invoice') }} </label>
                        </div>
                    </fieldset>

                </div>
                <div class="modal-footer justify-content-center">
                    <button type="submit" class="btn btn-primary">{{ __('Generate') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
    <script>
        $(document).ready(function () {
            dtDateRangePicker();

            const select = $('#customers-select')

            select.select2({
                multiple: true,
                placeholder: '{{ __('Search') }}',
            });

            select.val(null).trigger('change');

            select.on('select2:select', function (e) {
                const data = e.params.data;
                if (data.id === 'all') {
                    const customers = @json($customers);
                    const customersIds = Object.keys(customers);

                    select.val(customersIds).trigger('change');
                }
            });

            select.on('select2:unselect', function (e) {
                const currentSelectedValues = select.select2('data').map(item => item.id);
                select.val(currentSelectedValues.filter(value => value !== 'all')).trigger('change');
            });
        })
    </script>
@endpush
