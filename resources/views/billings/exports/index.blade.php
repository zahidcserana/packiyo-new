@extends('layouts.app', ['title' => __('Orders'), 'submenu' => 'orders.menu'])

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col col-12">
                <div class="card table-card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-6 d-flex align-items-center">
                                <h3 class="mb-0">{{ __('3PL Billing') }}</h3>
                            </div>
                        </div>
                        @include('billings.menuLinks', ['active' => 'exports'])
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.footers.auth')
    </div>
@endsection
