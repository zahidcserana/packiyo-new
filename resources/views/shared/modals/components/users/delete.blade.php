<div class="modal fade confirm-dialog" id="delete-user-modal" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content bg-white">
            <div class="modal-header border-bottom">
                <h6 class="modal-title">{{ __('Delete User') }} <span class="opacity-4 font-weight-400 font-xs delete-user-email"></span></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 d-flex align-items-center justify-content-between">
                        <h3 class="p-0 m-0 mr-3">{{ __('Are you sure you want to delete this user?') }}</h3>
                        <label class="toggle m-0">
                            <input type="checkbox" name="confirm_user_delete">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top d-flex justify-content-center">
                <button data-dismiss="modal" class="btn mx-2">{{ __('Cancel') }}</button>
                <button class="btn mx-2 delete-user-button">{{ __('Save') }}</button>
            </div>
        </div>
    </div>
</div>
