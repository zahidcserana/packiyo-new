@extends('layouts.app')
@section('content')
    @component('layouts.headers.auth', [ 'title' => __('Packaging'), 'subtitle' => __('Add'), 'buttons' => [['title' => __('Back to list'), 'href' => route('shipping_box.index')]]])
    @endcomponent
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="table-responsive p-4">
                        <form method="post" action="{{ route('shipping_box.store') }}" autocomplete="off">
                            @csrf
                            <div class="pl-lg-4">
                                @include('shipping_box.shippingBoxInformationFields')
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
        new ShippingBox()
    </script>
@endpush
