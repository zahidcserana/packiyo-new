@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => __('Pack Orders'),
        'tabs' => [
            [
                'name' => 'Single Orders',
                'route' => route('packing.index'),
            ],
            [
                'name' => 'Bulk Ship',
                'route' => 'current',
            ],
        ]
    ])

    <div class="container-fluid pb-2 pb-md-3">
        <div class="flex">
            <a
                class="btn btn-white mx-auto border-0 {{ Route::is('bulk_shipping.index') ? 'active' : '' }}"
                href="{{ route('bulk_shipping.index') }}"
            >
                {{ __('Suggested') }}
            </a>
            <a
                class="btn btn-white mx-auto border-0 {{ Route::is('bulk_shipping.inProgress') ? 'active' : '' }}"
                href="{{ route('bulk_shipping.inProgress') }}"
            >
                {{ __('In Progress') }}
            </a>
            <a
                class="btn btn-white mx-auto border-0 {{ Route::is('bulk_shipping.batches') ? 'active' : '' }}"
                href="{{ route('bulk_shipping.batches') }}"
            >
                {{ __('Batches') }}
            </a>
        </div>
    </div>

    @if (Route::is(['bulk_shipping.index', 'bulk_shipping.inProgress']))
        <x-datatable
            search-placeholder="{{ __('Search order or tote') }}"
            table-id="bulk-shipping-table"
            table-class="table-hover"
            datatableOrder="{!! json_encode($datatableOrder) !!}"
            filters="local"
            filter-menu="shared.collapse.forms.bulk_shipping"
            bulkEdit=true
        />
    @else
        <x-datatable
            search-placeholder="{{ __('Search order or tote') }}"
            table-id="bulk-shipping-table"
            table-class="table-hover"
            datatableOrder="{!! json_encode($datatableOrder) !!}"
            filters="local"
            filter-menu="shared.collapse.forms.bulk_shipping"
        />
    @endif
@endsection

@push('js')
    <script>
        new BulkShipping(
            '{{ Route::is(['bulk_shipping.index', 'bulk_shipping.inProgress']) ? 'suggested' : 'batches' }}'
        )
    </script>
@endpush

