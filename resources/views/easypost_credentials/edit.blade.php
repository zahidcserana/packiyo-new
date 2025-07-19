@extends('layouts.app')

@section('content')
    @component('layouts.headers.auth', [
        'title' => __('Easypost credentials'),
        'subtitle' => __('Update')
    ])
    @endcomponent
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <a href="{{ route('customers.easypost_credentials.index', compact('customer')) }}" class="text-black font-sm font-weight-600 d-inline-flex align-items-center bg-white border-8 px-3 py-2 mt-3">
                <i class="picon-arrow-backward-filled icon-lg icon-black mr-1"></i>
                {{ __('Back') }}
            </a>
            <a href="{{ route('easypost.carrier_account.create', compact('easypostCredential')) }}" class="btn bg-logoOrange text-white my-2 px-3 py-2 font-weight-700 border-8">
                {{ __('Add carrier account') }}
            </a>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="table-responsive p-4">
                        <form method="post" action="{{ route('customers.easypost_credentials.update', ['customer' => $customer, 'easypost_credential' => $easypostCredential]) }}" id="create-easypost-credentials-form" autocomplete="off">
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
                                   'name' => 'api_key',
                                   'label' => __('API key'),
                                   'value' => $easypostCredential->api_key,
                                   'required' => true
                                ])

                                @include('shared.forms.input', [
                                   'name' => 'test_api_key',
                                   'label' => __('Test API key'),
                                   'value' => $easypostCredential->test_api_key,
                                   'required' => true
                                ])

                                @include('shared.forms.input', [
                                   'name' => 'commercial_invoice_signature',
                                   'label' => __('Commercial invoice signature'),
                                   'value' => $easypostCredential->commercial_invoice_signature,
                                ])

                                @include('shared.forms.input', [
                                   'name' => 'commercial_invoice_letterhead',
                                   'label' => __('Commercial invoice letterhead'),
                                   'value' => $easypostCredential->commercial_invoice_letterhead,
                                ])

                                @include('shared.forms.select', [
                                    'name' => 'endorsement',
                                    'label' => __('Endorsement'),
                                    'containerClass' => 'mb-3',
                                    'value' => $easypostCredential->endorsement,
                                    'options' => \App\Models\EasypostCredential::ENDORSEMENT
                                ])

                                @include('shared.forms.checkbox', [
                                    'name' => 'use_native_tracking_urls',
                                    'label' => __('Use native tracking URLs'),
                                    'checked' => $easypostCredential->use_native_tracking_urls,
                                ])

                                <div class="text-center">
                                    <button type="submit" class="btn bg-logoOrange mx-auto px-5 font-weight-700 mt-5 change-tab text-white">{{ __('Save') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="mt-5">
                        <div class="text-muted p-2">
                            <h2>{{ __('Carrier accounts') }}</h2>
                        </div>
                        <div class="table-responsive table-overflow items-table mb-4">
                            <table class="col-12 table table-flush">
                                <tbody id="item_container">
                                @foreach($carrierAccounts as $carrier)
                                    <tr>
                                        <td>
                                            {{ $carrier->name }}
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('easypost.carrier_account.edit', compact('easypostCredential', 'carrier')) }}" class="table-icon-button">
                                                <i class="picon-edit-filled icon-orange icon-lg" title="{{ __('Edit') }}"></i>
                                            </a>
                                            <form action="{{ route('easypost.carrier_account.delete', compact('easypostCredential', 'carrier')) }}" method="post" class="d-inline-block">
                                                <input type="hidden" name="_method" value="delete">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                                                <input type="hidden" name="easypost_credential_id" value="{{ $easypostCredential->id }}">
                                                <input type="hidden" name="carrier_account_id" value="{{ $carrier->settings['external_carrier_id'] }}">
                                                <button type="button" class="table-icon-button" data-confirm-message="{{ __('Are you sure you want to delete this carrier account?') }}" data-confirm-button-text="{{ __('Delete') }}">
                                                    <i class="picon-trash-filled icon-orange del_icon icon-lg" title="{{ __('Delete') }}"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
