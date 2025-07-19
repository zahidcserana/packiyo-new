@extends('layouts.app')

@section('content')
    @if($customer->count() > 1 || ($customer->count() === 1  && $customer->first()->isNotChild()) || auth()->user()->isAdmin())
        @include('layouts.headers.auth', [
            'title' => 'Settings',
            'subtitle' => 'Manage Customers',
            'buttons' => [
                [
                    'title' => __('Add customer'),
                    'href' => route('customer.create'),
                ]
            ]
        ])
    @else
        @include('layouts.headers.auth', [
            'title' => 'Settings',
            'subtitle' => 'Manage Customers',
        ])
    @endif

    <x-datatable
        search-placeholder="{{ __('Search customers') }}"
        table-id="customers-table"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
    />
@endsection

@push('js')
    <script>
        new Customer()
    </script>
@endpush
