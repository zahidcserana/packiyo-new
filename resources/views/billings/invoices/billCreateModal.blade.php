<div class="modal fade" id="invoice-create-modal" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="{{route('invoices.store')}}">
                @csrf
                <div class="modal-header px-0">
                    <div class="mx-4 pb-4 d-flex w-100 border-bottom-gray">
                        <h6 class="modal-title text-black text-left"
                            id="modal-title-notification">{{ __('Create Invoice') }}</h6>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                            <span aria-hidden="true" class="text-black">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body py-0">
                    <fieldset>
                        <div class="form-group md-3">
                            <label>{{__('Start Date')}}</label>
                            <input
                                type="text"
                                name="start_date"
                                class="datetimepicker form-control"
                                autocomplete="off"
                                placeholder="Dates between"
                                value={{$lastInvoiceEndDate}}
                            >
                            <label>{{__('End Date')}}</label>
                            <input
                                type="text"
                                name="end_date"
                                class="datetimepicker form-control"
                                placeholder="Dates between"
                                autocomplete="off"
                            >
                            <input name="customer_id" type="hidden" value="{{$customer->id}}">
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
            $('.datetimepicker').daterangepicker({
                singleDatePicker: true,
                timePicker: false,
                autoApply: true,
                autoUpdateInput:false,
                locale: {
                    format: 'Y-MM-DD'
                }
            });

            $('.datetimepicker').on('apply.daterangepicker', function(ev, picker) {
                $(this)
                    .val(moment(picker.startDate).format('Y-MM-DD'))
            });
        })
    </script>
@endpush
