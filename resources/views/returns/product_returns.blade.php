@extends('layouts.app')

@section('content')
    <div class="w-100">
        @include('layouts.headers.auth', [
            'title' => "Product's Returned Items"
        ])
        <x-datatable
            search-placeholder="{{ __('Search order number') }}"
            table-id="product-returns-table"
            datatableOrder="{!! json_encode($datatableProductReturn) !!}"
        />
    </div>                    
@endsection
@push('js')
    <script>
        let searchParams = new URLSearchParams(window.location.search)

        new ReturnOrder('', searchParams.get('product_id'), searchParams.get('from_date_created'), searchParams.get('to_date_created'))
    </script>
@endpush
