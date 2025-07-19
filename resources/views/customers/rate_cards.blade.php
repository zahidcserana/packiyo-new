@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Customers',
        'subtitle' =>  __('Edit rate cards')
    ])
    <div class="container-fluid bg-lightGrey select2Container">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <a href="{{ route('customer.index') }}" class="text-black font-sm font-weight-600 d-inline-flex align-items-center bg-white border-8 px-3 py-2 mt-3">
                <i class="picon-arrow-backward-filled icon-lg icon-black mr-1"></i>
                {{ __('Back') }}
            </a>
        </div>
        <div class="row">
            <div class="col-xl-12 order-xl-1">
                <div class="card">
                    <div class="card-body">
                        <div class="nav-wrapper">
                            <ul class="nav nav-pills nav-fill flex-column flex-md-row" id="tabs-icons-text" role="tablist">
                                <li class="nav-item">
                                    <div class="nav-link mb-sm-3 mb-md-0" id="tabs-icons-text-1-tab"
                                       aria-controls="tabs-icons-text-1" aria-selected="false"><i class="ni ni-cloud-upload-96 mr-2"></i>{{ __('Customer') }}</div>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mb-sm-3 mb-md-0" id="tabs-icons-text-2-tab" href="{{ route('customer.editUsers', [ 'customer' => $customer ]) }}" role="tab" aria-controls="tabs-icons-text-2" aria-selected="false"><i class="ni ni-bell-55 mr-2"></i>{{ __('Users') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mb-sm-3 mb-md-0" id="tabs-icons-text-3-tab" href="{{ route('customers.easypost_credentials.index', [ 'customer' => $customer ]) }}" role="tab" aria-controls="tabs-icons-text-2" aria-controls="tabs-icons-text-3" aria-selected="false"><i class="ni ni-bell-55 mr-2"></i>{{__('Easypost Credentials')}}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mb-sm-3 mb-md-0" id="tabs-icons-text-3-tab" href="{{ route('customers.webshipper_credentials.index', [ 'customer' => $customer ]) }}" role="tab" aria-controls="tabs-icons-text-2" aria-controls="tabs-icons-text-3" aria-selected="false"><i class="ni ni-bell-55 mr-2"></i>{{__('Webshipper Credentials')}}</a>
                                </li>
                                @if($customer->parent && auth()->user()->isAdmin())
                                <li class="nav-item">
                                    <a class="nav-link mb-sm-3 mb-md-0 active" id="tabs-icons-text-3-tab" href="{{ route('customers.rate_cards.edit', [ 'customer' => $customer ]) }}" role="tab" aria-controls="tabs-icons-text-2" aria-controls="tabs-icons-text-3" aria-selected="true"><i class="ni ni-bell-55 mr-2"></i>{{__('Rate cards')}}</a>
                                </li>
                                @endif
                            </ul>
                        </div>
                        <div class="card shadow">
                            <div class="card-body">
                                <form method="post" id="create-customer-form" action="{{ route('customers.rate_cards.update', [ 'customer' => $customer ]) }}" autocomplete="off" enctype="multipart/form-data">
                                    @csrf
                                    <div class="pl-lg-4">
                                        <div class="form-group">
                                            @include('shared.forms.select', [
                                               'name' => 'primary_rate_card_id',
                                               'label' => __('Rate card'),
                                               'value' => $customer->primaryRateCard()->id ?? '',
                                               'options' => $rateCards,
                                               'allowClear' => true
                                            ])
                                        </div>

                                        <div class="d-flex justify-content-center">
                                            <button
                                                id="submit-button"
                                                class="btn bg-logoOrange text-white mx-auto px-5 font-weight-700 mt-5">
                                                {{ __('Save') }}
                                            </button>
                                        </div>
                                    </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script>
        window.ckeditor()
    </script>
@endpush
