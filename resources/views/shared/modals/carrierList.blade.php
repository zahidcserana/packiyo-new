<div class="modal fade confirm-dialog" id="carrierListModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <form method="post" action="{{ route('shipping_carrier.store') }}" autocomplete="off" data-type="POST" id="carrierCreateForm" enctype="multipart/form-data"
              class="modal-content">
            <div class="modal-header px-0">
                <div class="mx-4 pb-4 d-flex w-100 border-bottom-gray">
                    <h6 class="modal-title text-black text-left"
                        id="modal-title-notification">{{ __('Add Carrier') }}</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                        <span aria-hidden="true" class="text-black">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body text-white text-center pb-3 pt-0 overflow-auto">
                @csrf
                <div class="nav-wrapper">
                    <ul class="nav nav-pills nav-fill flex-md-row" id="tabs-icons-text" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link mb-sm-3 mb-md-0 active" id="select-carrier-tab" data-toggle="tab"
                               href="#select-carrier-content" role="tab" aria-controls="tabs-icons-text-1"
                               aria-selected="true">
                                {{ __('Select a carrier') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link mb-sm-3 mb-md-0" id="connect-your-account" data-toggle="tab"
                               href="#connect-your-account-content" role="tab" aria-controls="tabs-icons-text-1"
                               aria-selected="true">
                                {{ __('Connect your account') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="tab-content text-black" id="myTabContent">
                    <div class="tab-pane fade show active" id="select-carrier-content" role="tabpanel"
                         aria-labelledby="select-carrier-tab">
                         <div class="container px-0">
                            <div class="row carrier-list"></div>
                         </div>

                         <button type="button" class="btn bg-logoOrange mx-auto px-5 font-weight-700 text-sm mt-5 change-tab text-white" data-id="#connect-your-account">
                            {{ __('Next') }}
                        </button>
                    </div>
                    <div class="tab-pane fade" id="connect-your-account-content" role="tabpanel"
                         aria-labelledby="connect-your-account">
                        <div class="container px-0">
                            <div class="row carrier-configurations">
                                @if(!isset($sessionCustomer))
                                    <div class="col-6">
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
                                    </div>
                                @else
                                    <input type="hidden" name="customer_id" value="{{ $sessionCustomer->id }}" class="customer_id" />
                                @endif
                            </div>

                            <div class="loading-img-div text-center d-none">
                                <img width="50px" src="{{ asset('img/loading.gif') }}">
                            </div>
                        </div>
                        <button class="btn bg-logoOrange text-white mx-auto px-5 font-weight-700 text-sm confirm-button mt-5" id="submitCreateButton">
                            {{ __('Create') }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
