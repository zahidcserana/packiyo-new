@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Settings',
        'subtitle' => 'Address Book',
        'buttons' => [
            [
                'title' => __('Add Address'),
                'href' => '#',
                'data-toggle' => 'modal',
                'data-target' => '#address-book-modal',
            ]
        ]
    ])

    <x-datatable
        search-placeholder="{{ __('Search address') }}"
        table-id="address-books-table"
        filters="local"
        filter-menu="shared.collapse.forms.address_books"
        datatableOrder="{!! json_encode($datatableOrder) !!}"
    >
    </x-datatable>

    @include('shared.modals.addressBookModals')
@endsection
@push('js')
    <script>
        new AddressBook()
    </script>
@endpush
