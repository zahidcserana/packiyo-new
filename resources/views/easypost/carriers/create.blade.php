@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => __('Easypost'),
        'subtitle' => __('Add carriers')
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
                        <form method="post" action="{{ route('easypost.carrier_account.store', compact('easypostCredential')) }}" autocomplete="off">
                            @csrf
                            <div>
                                <input type="hidden" name="easypost_credential_id" value="{{ $easypostCredential->id }}" />
                                <input type="hidden" name="customer_id" value="{{ $easypostCredential->customer->id }}" />

                                @include('shared.forms.select', [
                                            'name' => 'type',
                                            'label' => __('Carrier type'),
                                            'containerClass' => 'mb-3',
                                            'options' => $carrierTypes
                                    ])
                                @include('shared.forms.input', [
                                            'name' => 'description',
                                            'label' => __('Description')
                                        ])

                                <h3 class="text-muted">{{ __('Client credentials') }}</h3>
                                <div class="pl-3 client-credentials">

                                </div>
                                <div class="client-test-credentials">
                                    <h3 class="text-muted">{{ __('Client test credentials') }}</h3>
                                    <div class="pl-3" id="test-credentials">

                                    </div>
                                </div>
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

@push('js')
    <script>
        let carrierCredentials = {!! $carrierCredentials !!};

        new Easypost(carrierCredentials)
    </script>
@endpush
