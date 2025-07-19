<div class="modal fade confirm-dialog" id="carrierDisconnectionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <form method="post" action="" autocomplete="off" data-type="POST" id="carrierDisconnectionForm" enctype="multipart/form-data"
              class="modal-content">
            <div class="modal-header px-0">
                <div class="mx-4 pb-4 d-flex w-100 border-bottom-gray">
                    <h6 class="modal-title text-black text-left"
                        id="modal-title-notification">{{ __('Carrier Disconnection') }}</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                        <span aria-hidden="true" class="text-black">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body text-black text-center pb-3 pt-0 overflow-auto">
                @csrf
                <div class="container px-0">
                    <div class="row text-center">
                        <div class="col-12 font-weight-700"><i class="picon-trash-light icon-lg"></i></div>
                        <div class="col-12 font-weight-700 my-3 text-logo-orange">Disconnect</div>
                        <div class="col-12 font-weight-600 font-xs text-black"><p>Are you sure you want to disconnect this carrier?</p></div>
                        <div class="col-md-6 offset-md-3 col-sm-6 offset-sm-3"><input type="text" name="disconnection_text" placeholder="Type Disconnect" class="p-2 form-control input-group-sm font-sm bg-white font-weight-400 text-black h-auto text-center" id="disconnectionText"></div>
                    </div>
                </div>
                <button class="btn bg-logoOrange text-white mx-auto px-5 font-weight-700 text-sm confirm-button mt-5" id="submit-carrier-disconnection-form-button">
                    {{ __('OK') }}
                </button>
            </div>
        </form>
    </div>
</div>
