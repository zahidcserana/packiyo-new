@extends('layouts.app', ['title' => __('Rate Cards'), 'submenu' => 'billings.old.menu'])

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Billing',
        'subtitle' => 'Clients'
    ])

    <div class="container-fluid">
        <div class="row">
            <div class="col col-12">
                <div class="card table-card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-6">
                                <h3 class="mb-0">{{ __('Rate Cards') }}</h3>
                            </div>
                            <div class="col-6 text-right">
                                <a href="{{ route('rate_cards.create') }}" class="btn bg-logoOrange text-white mx-auto px-5 font-weight-700 mt-5">{{ __('Add rate card') }}</a>
                            </div>
                        </div>

                        <div class="table-responsive p-0">
                            <table class="table align-items-center table-hover col-12 p-0" id="rate-card-table" style="width: 100% !important;"></table>
                            <div class="d-flex loading-container justify-content-center align-items-center p-4">
                                <img width="50px" src="/img/loading.gif" alt="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        new RateCard();
    </script>
@endpush
