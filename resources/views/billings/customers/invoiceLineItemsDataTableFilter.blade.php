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
                <input type="hidden" id="table-id" value="invoice-line-items-table">
                <div class="form-group col-12 col-md-12">
                    <input type="text"  name="table_search" class=" form-control table_filter" placeholder="{{ __('Search invoice items') }}">
                </div>
            </div>
        </div>
    </div>
</div>
@push('table-addons-js')
    <script>
        new DataTableAddons();
    </script>
@endpush
