@extends('layouts.app')
@section('content')
    @component('layouts.headers.auth', [ 'title' => __('Totes'), 'subtitle' => __('Edit'), 'buttons' => [['title' => __('Back to list'), 'href' => route('tote.index')]]])
    @endcomponent
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="row w-100">
                        <div class="col-12 ">
                            <form method="post" action="{{ route('tote.clear', [ 'tote' => $tote ]) }}" autocomplete="off">
                                @csrf
                                <button type="submit" class="float-right btn-sm btn bg-logoOrange mx-auto px-5 font-weight-700 mt-5 change-tab text-white">{{ __('Clear tote items') }}</button>
                            </form>
                        </div>
                    </div>
                    <div class="table-responsive p-4">

                        <div class="row w-100">
                            <div class="col-12">
                                <form method="post" action="{{ route('tote.update', [ 'tote' => $tote ]) }}" autocomplete="off">
                                    @csrf
                                    <div class="pl-lg-4">
                                        {{ method_field('PUT') }}
                                        @include('totes.toteFormFields', [
                                            'tote' => $tote,
                                            'createForm' => false
                                        ])
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
            <div class="col-12">
                <div class="card">
                    <div class="py-3 px-4">
                        <div class="border-bottom  py-2 d-flex">
                            <h6 class="modal-title text-black text-left" id="modal-title-notification">
                                {{ __('Items') }}
                            </h6>
                        </div>
                        <div class="select-tabs d-flex text-center py-3 overflow-auto justify-content-between">
                            <x-datatable
                                search-placeholder="{{ __('Search tote items') }}"
                                table-id="totes-item-table"
                                datatableOrder="{!! json_encode($datatableOrder) !!}"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 border-12 p-0 m-0 mb-3 bg-white overflow-hidden">
                <div class="has-scrollbar py-3 px-4">
                    <div class="border-bottom  py-2 d-flex">
                        <h6 class="modal-title text-black text-left" id="modal-title-notification">{{ __('Tote Log') }}</h6>
                    </div>
                    <div class="select-tabs d-flex py-3 overflow-auto justify-content-between">
                        <div class="w-100">
                            <x-datatable
                                search-placeholder="{{ __('Search event') }}"
                                table-id="audit-log-table"
                                model-name="Tote"
                                datatableOrder="{!! json_encode($datatableAuditOrder) !!}"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script>
        new Tote({!! $tote->id !!})
    </script>
@endpush
