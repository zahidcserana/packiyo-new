<div class="modal fade confirm-dialog" id="orderChannelListModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <form method="post" action="/order_channels/connect" autocomplete="off" data-type="POST" id="orderChannelConnectionCreateForm" enctype="multipart/form-data"
              class="modal-content">
            @csrf
            <input type="hidden" id="external-integration-id" name="external_integration_id" value="{{ $credential->settings['external_integration_id'] ?? '' }}">
            <div class="modal-header px-0">
                <div class="mx-4 pb-4 d-flex w-100 border-bottom-gray">
                    <h6 class="modal-title text-black text-left"
                        id="modal-title-notification">{{ __('Available Connections') }}</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                        <span aria-hidden="true" class="text-black">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body text-white text-center pb-3 pt-0 overflow-auto" id="orderChannelListModalBody">
                <div class="nav-wrapper">
                    <ul class="nav nav-pills nav-fill flex-md-row" id="tabs-icons-text" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link mb-sm-3 mb-md-0 active" id="select-order-channel-tab" data-toggle="tab"
                               href="#select-order-channel-content" role="tab" aria-controls="tabs-icons-text-1"
                               aria-selected="true">
                                {{ __('Select a connection') }}
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
                    <div class="tab-pane fade show active" id="select-order-channel-content" role="tabpanel"
                         aria-labelledby="select-order-channel-tab">
                         <div class="container px-0">
                            <div class="row order-channel-list"></div>
                         </div>

                         <button type="button" class="btn bg-logoOrange mx-auto px-5 font-weight-700 text-sm mt-5 change-tab text-white" data-id="#connect-your-account">
                            {{ __('Next') }}
                        </button>
                    </div>
                    <div class="tab-pane fade" id="connect-your-account-content" role="tabpanel"
                         aria-labelledby="connect-your-account">
                        <div class="container px-0">
                            <input type="hidden" name="order_channel_type" value="">
                            <input type="hidden" id="skipoauth" name="skipoauth" value="{{ $skipoauth }}">
                            <input type="hidden" id="migrate_to_order_channel_id" name="migrate_to_order_channel_id" value="{{ request('migrate_to_order_channel_id', 0) }}">
                            <input type="hidden" id="oauth-connection" name="oauth_connection" value="">
                            <div class="form-group mb-0 mx-2 text-left mb-3 d-flex flex-column">
                                <label for="name" class="text-neutral-text-gray font-weight-600 font-xs">Name of the integration</label>
                                <div class="input-group input-group-merge bg-lightGrey font-sm ">
                                    <input id="name" class="configuration-value form-control font-sm font-weight-600 text-neutral-gray h-auto p-2" placeholder="Enter an integration name" type="text" name="name" value="">
                                </div>
                            </div>
                            <hr/>
                            <div class="row order-channel-configurations">
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
                                <span>Connecting and syncing data</span>
                            </div>
                        </div>
                        <button class="btn bg-logoOrange text-white mx-auto px-5 font-weight-700 text-sm confirm-button mt-5" id="submitCreateButton">
                            {{ __('Connect') }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
