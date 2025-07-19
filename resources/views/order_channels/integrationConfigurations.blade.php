@php
    $destination = isset($orderChannelDetails) ? collect($orderChannelDetails['data']['destination_connections'])->where('id', $orderChannel->settings['external_destination_connection_id'])->first() : null;
    
    $configurations = $destination['configuration'] ?? [];
@endphp
@if($destination)
<div class="card p-4 strech-container">
    <div class="border-bottom  py-2 d-flex">
        <h6 class="modal-title text-black text-left">
            {{ __('Manage Configurations - Integration') }}
        </h6>
    </div>
    <div class="d-flex text-left py-3 justify-content-between flex-column">
        <div class="align-items-center">
            <form action="{{route('order_channels.updateExternalDataflow', ['orderChannel' => $orderChannel->id]) }}" method="post">
                @csrf
                <div class="row justify-content-between">
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
                    <div class="col-8">
                        @include('shared.forms.input', [
                            'name' => 'name',
                            'label' => 'Name of the Integration',
                            'value' => $orderChannel->name ?? '',
                        ])
                    </div>
                    <div class="col-4 py-3 justify-content-start">
                        <button type="submit" class="btn bg-logoOrange mx-auto px-5 font-weight-700 mt-2 change-tab text-white">
                            {{ __('Update') }}
                        </button>                                
                    </div>
                </div>
            </form>
        </div>
        <div class="align-items-center">
            <form action="{{route('order_channels.updateUserName', ['orderChannel' => $orderChannel->id]) }}" method="post">
                @csrf
                <div class="row justify-content-between">
                    <div class="col-8">
                        @include('shared.forms.input', [
                            'name' => 'name',
                            'label' => 'Name of the Integration User',
                            'value' => $user->contactInformation->name ?? '',
                        ])
                    </div>
                    <div class="col-4 py-3 justify-content-start">
                        <button type="submit" class="btn bg-logoOrange mx-auto px-5 font-weight-700 mt-2 change-tab text-white">
                            {{ __('Update') }}
                        </button>                                
                    </div>
                </div>
            </form>
        </div>
        <div class="align-items-center">
            <form action="{{route('order_channels.refreshPackiyoAccessToken', ['orderChannel' => $orderChannel->id]) }}" method="post">
                @csrf
                <div class="row justify-content-between">
                    <div class="col-8">
                        @include('shared.forms.input', [
                            'name' => 'access_token',
                            'label' => 'Access Token',
                            'value' => collect($configurations)->where("connection_oriented", false)->where("field", "access_token")->first()["value"] ?? collect($configurations)->where("connection_oriented", true)->where("field", "access_token")->first()["value"] ?? '',
                            'readOnly' => 'readonly',
                            'class' => 'bg-white'
                        ])
                    </div>
                    <div class="col-4 py-3 justify-content-start">
                        <button type="submit" class="btn bg-logoOrange mx-auto px-5 font-weight-700 mt-2 change-tab text-white">
                            {{ __('Refresh') }}
                        </button>                                
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endif