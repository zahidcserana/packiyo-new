@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Inventory',
        'subtitle' => 'Location Log',
        'buttons' => [
            [
                'title' => __('Back'),
                'href' => route('location.index'),
            ]
        ]
    ])

    <x-datatable
        search-placeholder="{{ __('Search event') }}"
        table-id="audit-log-table"
        model-name="Location"
        datatableOrder="{!! json_encode($datatableAuditOrder) !!}"
    />
@endsection

@push('js')
    <script>
        new LocationForm(@json($location->id))
    </script>
@endpush
