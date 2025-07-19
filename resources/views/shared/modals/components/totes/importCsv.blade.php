<div class="modal fade confirm-dialog" id="import-totes-modal" role="dialog">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content bg-white">
            <form method="post" action="{{ route('tote.importCsv') }}" autocomplete="off" class="import-totes-form modal-content">
                @csrf
                <div class="modal-header border-bottom mx-4 px-0">
                    <h6 class="modal-title text-black text-left" id="modal-title-notification">{{ __('Import totes from CSV') }}</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                        <span aria-hidden="true" class="text-black">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center py-3 overflow-auto">
                    <div class="justify-content-md-between inputs-container">
                        <div class="w-50">
                            @if(!isset($sessionCustomer))
                                <div class="searchSelect">
                                    @include('shared.forms.new.ajaxSelect', [
                                    'url' => route('user.getCustomers'),
                                    'name' => 'customer_id',
                                    'className' => 'ajax-user-input customer_id',
                                    'placeholder' => __('Select customer'),
                                    'label' => __('Customer'),
                                    'default' => [
                                        'id' => old('customer_id'),
                                        'text' => ''
                                    ],
                                    'fixRouteAfter' => '.ajax-user-input.customer_id'
                                ])
                                </div>
                            @else
                                <input type="hidden" name="customer_id" value="{{ $sessionCustomer->id }}" class="customer_id" />
                            @endif
                        </div>
                        <div class="w-100">
                            <div class="form-group mb-0 mx-2 text-left mb-3">
                                <div class="">
                                    <div class="table-responsive supplier_container">
                                        <h6 class="heading-small text-muted mb-4">{{ __('Upload CSV file') }}</h6>
                                        <table class="col-12 table align-items-center table-flush">
                                            <tbody>
                                                <tr>
                                                    <td style="white-space: unset">
                                                        <div class="form-group mx-2 text-center">
                                                            <label
                                                                for="import_csv"
                                                                data-id="import_csv"
                                                                class="text-neutral-text-gray font-weight-600 font-xs"
                                                            >
                                                            </label>
                                                            <div
                                                                class="input-group input-group-alternative input-group-merge bg-lightGrey font-sm">
                                                                <input
                                                                    class="form-control font-sm bg-lightGrey font-weight-600 text-neutral-gray h-auto p-2"
                                                                    placeholder="{{ __('Upload CSV file') }}"
                                                                    type="file"
                                                                    name="import_csv"
                                                                    id="totes-csv-button"
                                                                    accept=".csv"
                                                                    style="display: none"
                                                                >
                                                            </div>
                                                            <button
                                                                onclick="document.getElementById('totes-csv-button').click()"
                                                                class="btn bg-logoOrange mx-auto px-5 text-white uploadCsv"
                                                                type="button"
                                                            >
                                                                {{ __('Import CSV file') }}
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div id="csv-filename" class="d-flex justify-content-center">

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="alert-container"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn bg-logoOrange mx-auto px-5 text-white import-totes">{{ __('Import') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
