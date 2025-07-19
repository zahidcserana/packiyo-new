@extends('layouts.app', ['title' => __('Product profiles')])

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col col-12">
                <div class="card">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center table-hover col-12 p-0" id="billing-rates-table" style="width: 100% !important;">
                            <thead class="thead-light"></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.footers.auth')
    </div>
@endsection
@push('js')
    <script>
        new BillingRate();
    </script>
@endpush
