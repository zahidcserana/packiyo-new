<div class="col-12">
    <a class="btn btn-sm btn-primary btn-block text-white mb-4 d-block d-sm-none toggle-filter-button">
        <span>{{ __('Show filter') }}</span>
        <span>{{ __('Close filter') }}</span>
    </a>
</div>
<div class="col-12 d-none d-sm-block filter">
    <div class="card">
        <div class="card-header">
            <h3>Filter sources</h3>
        </div>
        <div class="card-body pt-0 pb-0">
            <div class="row">
                <input type="hidden" id="table-id" value="invoices-table">
                <div class="form-group col-12 col-md-3 calendar-input">
                    <input
                        type="text"
                        name="dates_between"
                        class="table-datetimepicker table_filter form-control dates_between"
                        placeholder="Dates between"
                        value=""
                    >
                </div>
                <div class="form-group col-12">
                    <button data-href="{{route('invoices.export_invoice_lines')}}"
                            class="btn btn-primary text-white export-invoices">{{__('Export Invoice Lines')}}</button>
                </div>
                <div class="form-group col-12">
                    <button data-href="{{route('invoices.export_invoice_header')}}"
                            class="btn btn-primary text-white export-invoices">{{__('Export Invoice Header')}}</button>
                </div>
            </div>
        </div>
    </div>
</div>
@push('table-addons-js')
    <script>
        new DataTableAddons();

        $(document).ready(function () {
            $(".export-invoices").click(function () {
                let url = $(this).data('href') + "?date_range=" + $('.dates_between').val();
                window.open(url, '_blank');
            })
        });

        new BillingInvoices(true);

    </script>
@endpush
