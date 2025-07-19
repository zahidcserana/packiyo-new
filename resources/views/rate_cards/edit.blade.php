@extends('layouts.app', ['title' => __('Rate Cards'), 'submenu' => 'billings.old.menu'])

@section('content')

<div class="row mr-0">
    <div class="col">
        @include('layouts.headers.auth', [
            'title' => 'Billing',
            'subtitle' => 'Rate Cards / ' . $rateCard->name
        ])
    </div>
    <div class="col d-flex align-items-center pr-0">
        <div class="container-fluid d-flex justify-content-end">
            <div class="btn-group">
                <a class="btn btn-primary text-white" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    {{ __( "New Rate") }}
                </a>
                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split opacity-8" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="sr-only">{{ __( "Toggle Dropdown" ) }}</span>
                </button>
                <div class="dropdown-menu">
                    @foreach(\App\Models\BillingRate::BILLING_RATE_TYPES as $type => $info)
                        <a  class="dropdown-item" href="{{ route('billing_rates.create', ['rate_card' => $rateCard, 'type' => $type ]) }}">
                            {{ __( $info['title'] ) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    @include('billings.menuLinks', ['active' => 'rate-cards'])

    <div class="row">
        <div class="col col-12">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <h2 class="m-0">
                        <a class="mr-2">
                            <i class="picon-edit-filled icon-lg" data-target="#rate-card-update-modal" data-toggle="modal" title="Edit"></i>
                        </a>
                        {{ __($rateCard->name) }}
                    </h2>
                    <a href="{{ route('billings.rate_cards') }}" class="text-black font-sm font-weight-600 d-inline-flex align-items-center bg-white border-8 px-3 py-2">
                        <i class="picon-arrow-backward-filled icon-lg icon-black"></i>
                        {{ __('Back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('rate_cards.billingRates')
</div>

<div class="modal fade" id="rate-card-update-modal" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="post" action="{{ route('rate_cards.update', [ 'rate_card' => $rateCard, 'id' => $rateCard->id ]) }}" autocomplete="off">
                @csrf
                <div class="modal-header px-0">
                    <div class="mx-4 pb-4 d-flex w-100 border-bottom-gray">
                        <h6 class="modal-title text-black text-left"
                            id="modal-title-notification">{{ __('Update Rate Card') }}</h6>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                            <span aria-hidden="true" class="text-black">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body py-0">
                    {{ method_field('PUT') }}
                    @include('rate_cards.rateCardInformationFields', [
                        'rateCard' => $rateCard
                    ])
                </div>
                <div class="modal-footer justify-content-center {{$isReadonlyUser ?? '' ? 'd-none' : '' }}">
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
