@extends('layouts.app')

@section('content')
    @if(auth()->user()->isAdmin())
        @include('layouts.headers.auth', [
            'title' => 'Settings',
            'subtitle' => 'Manage Users',
            'buttons' => [
                [
                    'title' => __('New User'),
                    'href' => '#',
                    'data-toggle' => 'modal',
                    'data-target' => '#create-user-modal',
                ]
            ]
        ])
    @else
        @include('layouts.headers.auth', [
            'title' => 'Settings',
            'subtitle' => 'Manage Users'
        ])
    @endif

    <x-datatable
        search-placeholder="Search User"
        table-id="users-table"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
        bulkEdit=true
    />

    @if(auth()->user()->isAdmin())
        @include('shared.modals.components.users.create')
        @include('shared.modals.components.users.delete')
    @endif
    @include('shared.modals.components.users.edit')
@endsection

@push('js')
    <script>
        new User()
    </script>
@endpush

