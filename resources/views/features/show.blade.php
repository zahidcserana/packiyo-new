@extends('layouts.app')

@section('content')
    @component('layouts.headers.auth', [
        'title' => __('Settings'),
        'subtitle' => __('Features')
    ])
    @endcomponent
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="post" action="{{ route('features.update') }}" autocomplete="off" enctype="multipart/form-data">
                            @csrf

                            <div class="form-group d-flex flex-column">
                                <label for="{{ \App\Features\LoginLogo::class }}" class="form-label font-weight-600">{{ __('Logo for Login Page') }}</label>
                                <div class="position-relative avatar height-200 width-200">
                                    <img src="{{ Feature::for('instance')->value(\App\Features\LoginLogo::class) ?? '/img/packiyo-logo-on-transparent.png' }}" data-default-src="/img/packiyo-logo-on-transparent.png" class="object-fit-contain height-200 width-200 login-logo-preview" alt={{ __('Login Logo') }}>
                                    <button class="btn btn-white rounded-circle justify-content-center align-items-center position-absolute circle-icon-button right--3 top--3 p-0 height-36 width-36 delete-login-logo-button m-0 {{Feature::for('instance')->value(\App\Features\LoginLogo::class) ? 'd-flex' : 'd-none'}}">
                                        <i class="picon-trash-filled icon-orange"></i>
                                    </button>
                                    <button class="btn btn-white rounded-circle justify-content-center align-items-center position-absolute circle-icon-button right--3 top--3 p-0 height-36 width-36 change-login-logo-button m-0 {{Feature::for('instance')->value(\App\Features\LoginLogo::class) ? 'd-none' : 'd-flex'}}">
                                        <i class="picon-edit-filled icon-orange"></i>
                                    </button>
                                </div>
                                <input hidden type="file" name="{{ \App\Features\LoginLogo::class }}" value="{{ Feature::for('instance')->value(\App\Features\LoginLogo::class) }}" class="login-logo-input">
                            </div>

                            <div class="form-group">
                                <span class="font-weight-600 mb-2 d-inline-block">{{ __('Instance Features') }}:</span>
                                @foreach($instanceFeatures as $instanceFeature)
                                    @include('shared.forms.checkbox', [
                                    'name' => 'features[' . $instanceFeature . ']',
                                    'label' => Str::headline(Str::afterLast($instanceFeature, '\\')),
                                    'checked' => Feature::for('instance')->active($instanceFeature),
                                ])
                                @endforeach

                                @if(!empty($customerFeatures))
                                    <br />
                                    <span class="font-weight-600 mb-2 d-inline-block">{{ __('Customer Features') }}:</span>

                                    @foreach($selectedCustomers as $customer)

                                        <br/>
                                        <span class="font-weight-450 mb-2 d-inline-block">{{ $customer->contactInformation->name }}</span>
                                        @foreach($customerFeatures as $customerFeature)
                                            @include('shared.forms.checkbox', [
                                            'name' => 'customerFeatures[' . $customerFeature . ']',
                                            'label' => Str::headline(Str::afterLast($customerFeature, '\\')),
                                            'checked' => Feature::for($customer)->active($customerFeature),
                                        ])
                                        @endforeach
                                        @include('shared.forms.input', [
                                            'name' => 'customer_id',
                                            'label' => __('customer_id'),
                                            'value' => $customer->id,
                                            'type' => 'hidden'
                                        ])

                                    @endforeach

                                @endif
                            </div>

                            <button
                                type="submit"
                                class="btn bg-logoOrange mx-auto px-5 font-weight-700 text-white d-block">
                                {{ __('Save') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        FeaturesForm();
    </script>
@endpush
