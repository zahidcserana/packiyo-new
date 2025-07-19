@extends('auth.container')

@section('container.content')
    <div style="max-width: 600px;" class="card m-0 w-100">
        <div class="card-header">
            <h1 class="m-0 font-md">{{ __('Reset Password') }}</h1>
        </div>
        <div class="card-body">
            <form role="form" class="loginForm m-0" method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="form-group{{ $errors->has('email') ? ' has-danger' : '' }} mb-0">
                    <div class="input-group input-group-alternative">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="ni ni-email-83"></i></span>
                        </div>
                        <input class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" placeholder="{{ __('Email') }}" type="email" name="email" value="{{ old('email') }}" required autofocus>
                    </div>
                    @if ($errors->has('email'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('email') }}</strong>
                        </span>
                    @endif
                </div>
                <button type="submit" class="btn btn-dark text-white w-100 my-3">{{ __('Send Password Reset Link') }}</button>
                <a href="{{ route('login') }}" class="text-gray-dark d-flex align-items-center justify-content-start">
                    <i class="picon-arrow-backward-light mr-2"></i>{{ __('Back to login') }}
                </a>
            </form>
        </div>
    </div>
@endsection
