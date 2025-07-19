<div class="modal fade" id="add-hoc-modal" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="{{route('invoices.ad_hoc', ['invoice' => $invoice])}}">
                @csrf
                <div class="modal-header px-0">
                    <div class="mx-4 pb-4 d-flex w-100 border-bottom-gray">
                        <h6 class="modal-title text-black text-left"
                            id="modal-title-notification">{{ __('Ad Hoc') }}</h6>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                            <span aria-hidden="true" class="text-black">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body py-0">
                    <fieldset>
                            <div class="form-group">
                                <label class="form-control-label">
                                    {{ __('Rate') }}
                                </label>
                                <select
                                    name="billing_rate_id"
                                    class="form-control"
                                    data-placeholder="{{ __('Choose...') }}"
                                    data-toggle="select"
                                >
                                    @foreach ($adHocs as $adHoc)
                                        <option
                                            value="{{ $adHoc->id }}"
                                            {{empty($adHoc->settings['fee']) ? 'disabled' : ''}}
                                        >
                                            {{ __('Name: ') }}{{$adHoc->name}} -
                                            {{ __('Description: ') }}{{$adHoc->description}} -
                                            {{ __('Unit: ') }}{{ $adHoc->settings['unit'] ?? 'NO UNIT'}}  -
                                            {{ __('Unit Rate: ') }}{{ empty($adHoc->settings['fee']) ? 'NO RATE' : $adHoc->settings['fee']}}
                                        </option>
                                    @endforeach
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
                                <input
                                    type="text"
                                    name="period_end"
                                    class="datetimepicker form-control"
                                    placeholder="{{ __('Date') }}"
                                    value=""
                                    autocomplete="off"
                                >
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
                autoUpdateInput: false,
                locale: {
                    format: 'Y-MM-DD'
                }
            });

            $('.datetimepicker').on('apply.daterangepicker', function(ev, picker) {
                $('.datetimepicker')
                    .val(moment(picker.startDate).format('Y-MM-DD'))
            });
        })
    </script>
@endpush
