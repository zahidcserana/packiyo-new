<div class="modal fade confirm-dialog" id="pack-in-quantities-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header px-0">
                <div class="mx-4 pb-4 d-flex w-100">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                        <span aria-hidden="true" class="text-black">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body text-white text-center overflow-auto pb-0 pt-0">
                <p class="text-black font-md font-weight-600 px-4">
                    {{ __('How many items do you want to pack?') }}
                </p>

                @include('shared.forms.input', [
                    'name' => 'quantity_to_pack',
                    'label' => '',
                    'min' => 1,
                    'labelClass' => 'd-none',
                    'value' => 1,
                    'type' => 'number',
                    'containerClass' => 'mb-0',
                    'class' => 'text-center',
                ])
            </div>
            <div class="modal-footer mx-auto">
                <button
                    id="pack-in-quantities-submit"
                    type="button"
                    class="confirm-button btn bg-blue text-white px-5 font-weight-700 text-sm change-tab"
                    data-dismiss="modal"
                    data-item-row-id=""
                >
                    {{ __('Pack') }}
                </button>
            </div>
        </div>
    </div>
</div>
