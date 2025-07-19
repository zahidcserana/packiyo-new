<div class="modal fade confirm-dialog" id="create-new-lot-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="modal-title-notification">{{ __('Create a new Lot') }}</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-white text-center py-3">
                <form action="{{ route('lot.store') }}" method="POST" id="create-new-lot-form">
                    @csrf
                <div class="row">
                    <div class="col-6">
                        @include('shared.forms.new.ajaxSelect', [
                            'url' => route('product.filterSuppliers'),
                            'name' => 'supplier_id',
                            'className' => 'ajax-user-input supplier_id',
                            'placeholder' => __('Select supplier'),
                            'label' => __('Supplier'),
                            'id' => 'lot-supplier-id'
                        ])
                        </div>
                        <div class="col-6">
                            <div class="row">
                                <div class="col-12">
                                    @include('shared.forms.input', [
                                        'name' => 'name',
                                        'id' => 'lot_name',
                                        'type' => 'text',
                                        'label' => __('Lot Name'),
                                        'required' => true
                                    ])
                                </div>
                                <div class="col-12">
                                    @include('shared.forms.input', [
                                        'name' => 'expiration_date',
                                        'id' => 'expiration_date',
                                        'type' => 'date',
                                        'label' => __('Expiration date')
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn bg-logoOrange text-white mx-auto px-5 confirm-button save-lot-button">{{ __('Save lot') }}</button>
            </div>
        </div>
    </div>
</div>
