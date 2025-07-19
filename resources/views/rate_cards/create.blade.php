@extends('layouts.app', ['title' => __('Rate Cards'), 'submenu' => 'billings.old.menu'])

@section('content')
@include('layouts.headers.auth', [
'title' => 'Billing',
'subtitle' => 'Rate Cards / New Rate Card'
])

<div class="container-fluid">
    @include('billings.menuLinks', ['active' => 'rate-cards'])

    <div class="row">
        <div class="col col-12">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <h2 class="m-0 ml-2">{{ __('New Rate Card') }}</h2>
                    <a href="{{ url()->previous() }}" class="text-black font-sm font-weight-600 d-inline-flex align-items-center bg-white border-8 px-3 py-2">
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
                    <form method="post" action="{{ route('rate_cards.store') }}" autocomplete="off">
                        @csrf
                        @include('rate_cards.rateCardInformationFields')
                        @if (auth()->user()->isAdmin())
                        <div class="form-group">
                            <label class="form-control-label">{{ __('3PL') }}</label>
                            <select name="3pl_id" class="form-control" data-toggle="select" data-placeholder="Select 3PL">
                                @foreach($threePls as $key => $value)
                                <option value="{{$key}}">
                                    {{$value}}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <input type="hidden" name="3pl_id" value="{{auth()->user()->customers()->first()->id ?? ''}}">
                        @endif
                        <div class="text-center">
                            <button type="submit" class="btn bg-logoOrange text-white mx-auto px-5 font-weight-700 mt-2">{{ __('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
