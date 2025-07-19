@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">
        <div class="row m-0">
            <div class="col-12 col-md-6 p-0">
                <img class="w-100 object-fit-cover login-cover" src="{{ asset('img/login-cover.jpg') }}" alt="Login Cover">
            </div>
            <div class="col-12 col-md-6 d-flex align-items-center justify-content-center flex-column">
                <div class="d-flex justify-content-center w-100">
                    <img height="200" width="200" class="object-fit-contain text-center login-logo" src="{{  login_logo() }}" alt="Logo">
                </div>
                @yield('container.content')
            </div>
        </div>
    </div>
@endsection
