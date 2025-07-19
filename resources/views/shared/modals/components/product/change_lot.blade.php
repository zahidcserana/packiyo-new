<div class="modal fade confirm-dialog" id="change-lot-modal" data-positioned="true" role="dialog">
    <div class="modal-dialog modal-md mx-0" role="document">
        <div class="modal-content bg-white">
            <form method="post" action="{{ route('product.change_location_lot', ['product' => $product]) }}" autocomplete="off" class="modal-content">
                @csrf
                <div class="modal-header border-bottom mx-4 px-0">
                    <h6 class="modal-title text-black text-left" id="modal-title-notification">{{ __('Change lot') }}</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                        <span aria-hidden="true" class="text-black">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center py-3 overflow-auto">
                    <div class="justify-content-md-between inputs-container">
                        <div class="w-100">
                            <div class="searchSelect">
                                <input type="hidden" name="product_id" />
                                <input type="hidden" name="location_id" />
                                <input type="hidden" name="lot_item_id" />
                                @include('shared.forms.ajaxSelect', [
                                    'url' => route('lot.filterLots') . '?product_id=' . $product->id,
                                    'name' => 'lot_id',
                                    'className' => 'ajax-user-input',
                                    'placeholder' => __('Search for a lot'),
                                    'label' => __('Change to')
                                ])
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn bg-logoOrange mx-auto px-5 text-white">{{ __('Change') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
