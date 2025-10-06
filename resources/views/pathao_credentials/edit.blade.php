@extends('layouts.app')

@section('content')
    @component('layouts.headers.auth', [
        'title' => __('Pathao credentials'),
        'subtitle' => __('Update')
    ])
    @endcomponent
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <a href="{{ route('customers.pathao_credentials.index', compact('customer')) }}" class="text-black font-sm font-weight-600 d-inline-flex align-items-center bg-white border-8 px-3 py-2 mt-3">
                <i class="picon-arrow-backward-filled icon-lg icon-black mr-1"></i>
                {{ __('Back') }}
            </a>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="table-responsive p-4">
                        <form method="post" action="{{ route('customers.pathao_credentials.update', ['customer' => $customer, 'pathao_credential' => $pathaoCredential]) }}" id="create-pathao-credentials-form" autocomplete="off">
                            @csrf
                            {{ method_field('PUT') }}
                            <div class="pl-lg-4">
                                @if(!isset($customer))
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
                                    @if(isset($customer))
                                        <input type="hidden" name="customer_id" value="{{ $customer->id }}" class="customer_id" />
                                    @elseif(isset($sessionCustomer))
                                        <input type="hidden" name="customer_id" value="{{ $sessionCustomer->id }}" class="customer_id" />
                                    @endif
                                @endif
                                @include('shared.forms.input', [
                                   'name' => 'api_base_url',
                                   'label' => __('API base URL'),
                                   'value' => $pathaoCredential->api_base_url,
                                   'required' => true
                                ])
                                @include('shared.forms.input', [
                                   'name' => 'client_id',
                                   'label' => __('Client ID'),
                                   'value' => $pathaoCredential->client_id,
                                   'required' => true
                                ])
                                @include('shared.forms.input', [
                                   'name' => 'client_secret',
                                   'label' => __('Client Secret'),
                                   'value' => $pathaoCredential->client_secret,
                                   'required' => true
                                ])
                                @include('shared.forms.input', [
                                   'name' => 'username',
                                   'label' => __('Username'),
                                   'value' => $pathaoCredential->username,
                                   'required' => true
                                ])
                                @include('shared.forms.input', [
                                   'name' => 'password',
                                   'label' => __('Password'),
                                   'value' => $pathaoCredential->password,
                                   'required' => true
                                ])
                                @include('shared.forms.input', [
                                  'name' => 'store_id',
                                  'label' => __('Store ID'),
                                  'value' => $pathaoCredential->store_id,
                                  'required' => true
                                ])
                                <div class="text-center">
                                    <button type="submit" class="btn bg-logoOrange mx-auto px-5 font-weight-700 mt-5 change-tab text-white">{{ __('Save') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
