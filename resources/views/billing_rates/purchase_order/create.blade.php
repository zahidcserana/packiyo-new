@extends('layouts.app', ['title' => __('Rate Cards'), 'submenu' => 'billings.old.menu'])

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Billing',
        'subtitle' => 'Rate Cards / ' . $rateTitle . ' Rate'
    ])

    <div class="container-fluid">
        @include('billings.menuLinks', ['active' => 'rate-cards'])

        <div class="row">
            <div class="col col-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <h2 class="m-0 ml-2">{{ __('Add '. $rateTitle .' Rate') }}</h2>
                        <a href="{{ route('rate_cards.edit', ['rate_card' => $rateCard]) . '#' . $rateType }}" class="text-black font-sm font-weight-600 d-inline-flex align-items-center bg-white border-8 px-3 py-2">
                            <i class="picon-arrow-backward-filled icon-lg icon-black"></i>
                            {{ __('Back') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12 order-xl-1">
                <div class="card">
                    <div class="card-body">
                        <form id="rate-duplicate-check" method="post" action="{{ route('billing_rates.purchase_order.store', ['rate_card' => $rateCard->id]) }}" autocomplete="off">
                            @csrf
                            @include('billing_rates.purchase_order.informationFields')
                            <div class="text-center">
                                <button type="submit" class="btn bg-logoOrange mx-auto px-5 font-weight-700 mt-5 change-tab text-white">{{ __('Save') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('js')
        <script>
            new BillingRate;
        </script>
    @endpush
@endsection
