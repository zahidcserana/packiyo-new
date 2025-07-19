@extends('layouts.app')

@section('content')
    @component('layouts.headers.auth', [
        'title' => __('Easypost credentials'),
        'subtitle' => __('Add')
    ])
    @endcomponent
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <a href="{{ route('customers.easypost_credentials.index', compact('customer')) }}" class="text-black font-sm font-weight-600 d-inline-flex align-items-center bg-white border-8 px-3 py-2 mt-3">
                <i class="picon-arrow-backward-filled icon-lg icon-black mr-1"></i>
                {{ __('Back') }}
            </a>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="table-responsive p-4">
                        <form method="post" action="{{ route('customers.easypost_credentials.store', compact('customer')) }}" id="create-easypost-credentials-form" autocomplete="off">
                            @csrf
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
                                   'name' => 'api_key',
                                   'label' => __('API key'),
                                   'required' => true
                                ])

                                @include('shared.forms.input', [
                                   'name' => 'test_api_key',
                                   'label' => __('Test API key'),
                                   'required' => true
                                ])

                                @include('shared.forms.input', [
                                   'name' => 'commercial_invoice_signature',
                                   'label' => __('Commercial invoice signature'),
                                ])

                                @include('shared.forms.input', [
                                   'name' => 'commercial_invoice_letterhead',
                                   'label' => __('Commercial invoice letterhead'),
                                ])

                                @include('shared.forms.select', [
                                    'name' => 'endorsement',
                                    'label' => __('Endorsement'),
                                    'containerClass' => 'mb-3',
                                    'options' => \App\Models\EasypostCredential::ENDORSEMENT
                                ])

                                @include('shared.forms.checkbox', [
                                    'name' => 'use_native_tracking_urls',
                                    'label' => __('Use native tracking URLs'),
                                    'checked' => 0,
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
