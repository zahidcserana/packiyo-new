<div class="modal fade confirm-dialog" id="custom-package-modal" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content bg-white">
            <div class="modal-header border-bottom">
                <h6 class="modal-title">{{ __('Custom Package Details') }}<br><span class="font-xs font-weight-400">{{ __('Used for shipping rate calculation.') }}</span></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-4">
                        @include('shared.forms.input', [
                           'name' => 'custom-package-length',
                           'label' => __('Length'),
                           'type' => 'number'
                        ])
                    </div>
                    <div class="col-4">
                        @include('shared.forms.input', [
                           'name' => 'custom-package-width',
                           'label' => __('Width'),
                           'type' => 'number'
                        ])
                    </div>
                    <div class="col-4">
                        @include('shared.forms.input', [
                           'name' => 'custom-package-height',
                           'label' => __('Height'),
                           'type' => 'number'
                        ])
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top d-flex justify-content-center">
                <button data-dismiss="modal" class="btn mx-2">{{ __('Cancel') }}</button>
                <button type="button" data-dismiss="modal" data-toggle="modal" class="btn bg-blue text-white mx-2 confirm-custom-package">{{ __('Apply') }}</button>
            </div>
        </div>
    </div>
</div>

