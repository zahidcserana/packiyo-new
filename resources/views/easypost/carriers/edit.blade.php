@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => __('Easypost'),
        'subtitle' => __('Edit carrier')
    ])

    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <a href="{{ route('customers.easypost_credentials.index', ['customer' => $easypostCredential->customer]) }}" class="text-black font-sm font-weight-600 d-inline-flex align-items-center bg-white border-8 px-3 py-2 mt-3">
                <i class="picon-arrow-backward-filled icon-lg icon-black mr-1"></i>
                {{ __('Back') }}
            </a>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="table-responsive p-4">
                        <form method="post" action="{{ route('easypost.carrier_account.update', compact('easypostCredential', 'carrier')) }}" autocomplete="off">
                            @csrf
                            <div>
                                <input type="hidden" name="easypost_credential_id" value="{{ $easypostCredential->id }}" />
                                <input type="hidden" name="customer_id" value="{{ $easypostCredential->customer->id }}" />
                                <input type="hidden" name="carrier_account_id" value="{{ $carrierAccount['id'] }}">

                                @include('shared.forms.input', [
                                             'name' => 'type',
                                             'label' => __('Carrier Type'),
                                             'readOnly' => 'readonly',
                                             'value' => $carrierAccount['type']
                                         ])
                                @include('shared.forms.input', [
                                            'name' => 'description',
                                            'label' => __('Description'),
                                            'readOnly' => 'readonly',
                                            'value' => $carrierAccount['description']
                                        ])
                                @include('shared.forms.input', [
                                            'name' => 'reference',
                                            'label' => __('Reference'),
                                            'readOnly' => 'readonly',
                                            'value' => $carrierAccount['reference']
                                        ])

                                <h3 class="text-muted">{{ __('Client credentials') }}</h3>
                                <div class="pl-3 client-credentials">
                                    @foreach($carrierAccount['fields']['credentials'] as $key => $inputField)
                                        @if($inputField['visibility'] === 'checkbox')
                                            <div class="form-group">
                                                @include('shared.forms.checkbox', [
                                                    'name' => "credentials[$key]",
                                                    'label' => $inputField['label'],
                                                    'checked' => (bool) $inputField['value']
                                                ])
                                            </div>
                                        @elseif($inputField['visibility'] === 'password' || $inputField['visibility'] === 'masked')
                                            @include('shared.forms.input', [
                                                    'name' => "credentials[$key]",
                                                    'label' => $inputField['label'],
                                                    'placeholder' => __('Leave blank to keep current'),
                                                    'value' => null
                                                ])
                                        @else
                                            @include('shared.forms.input', [
                                                'name' => "credentials[$key]",
                                                'label' => $inputField['label'],
                                                'value' => $inputField['value']
                                            ])
                                        @endif
                                    @endforeach
                                </div>
                                @if(!empty($carrierAccount['fields']['test_credentials']))
                                    <div class="client-test-credentials">
                                        <h3 class="text-muted">{{ __('Client test credentials') }}</h3>
                                        <div class="pl-3" id="test-credentials">
                                            @foreach($carrierAccount['fields']['test_credentials'] as $key => $inputField)
                                                @if($inputField['visibility'] === 'checkbox')
                                                    <div class="form-group">
                                                        @include('shared.forms.checkbox', [
                                                            'name' => "test_credentials[$key]",
                                                            'label' => $inputField['label'],
                                                            'checked' => (bool) $inputField['value']
                                                        ])
                                                    </div>
                                                @elseif($inputField['visibility'] === 'password' || $inputField['visibility'] === 'masked')
                                                    @include('shared.forms.input', [
                                                            'name' => "test_credentials[$key]",
                                                            'label' => $inputField['label'],
                                                            'placeholder' => __('Leave blank to keep current'),
                                                            'value' => null
                                                        ])
                                                @else
                                                    @include('shared.forms.input', [
                                                        'name' => "test_credentials[$key]",
                                                        'label' => $inputField['label'],
                                                        'value' => $inputField['value']
                                                    ])
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

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
