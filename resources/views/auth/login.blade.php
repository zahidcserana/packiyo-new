@extends('auth.container')

@section('container.content')
    <div style="max-width: 600px;" class="card m-0 w-100">
        <div class="card-header">
            <h1 class="m-0 font-md">{{ __('Sign In') }}</h1>
        </div>
        <div class="card-body">
            <form role="form" class="loginForm m-0" method="POST" action="{{ route('login') }}">
                @csrf
                <input type="hidden" name="timezone" value="">
                <div class="form-group{{ $errors->has('email') ? ' has-danger' : '' }} mb-3">
                    <div class="input-group input-group-alternative">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="far fa-envelope"></i></span>
                        </div>
                        <input class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" placeholder="{{ __('Email') }}" type="email" name="email" value="{{ old('email') }}" required autofocus>
                    </div>
                    @if ($errors->has('email'))
                        <span class="invalid-feedback" style="display: block;" role="alert">
                                    <strong>{{ $errors->first('email') }}</strong>
                                </span>
                    @endif
                </div>
                <div class="form-group{{ $errors->has('password') ? ' has-danger' : '' }}">
                    <div class="input-group input-group-alternative">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-unlock-alt"></i></span>
                        </div>
                        <input class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" placeholder="{{ __('Password') }}" type="password" required>
                    </div>
                    @if ($errors->has('password'))
                        <span class="invalid-feedback" style="display: block;" role="alert">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </span>
                    @endif
                </div>
                <div class="d-flex justify-content-between">
                    <div class="custom-control custom-control-alternative custom-checkbox font-weight-600">
                        <input class="custom-control-input" name="remember" id="customCheckLogin" type="checkbox" {{ old('remember') ? 'checked' : '' }}>
                        <label class="custom-control-label" for="customCheckLogin">
                            <span class="text-black font-xs">{{ __('Remember me') }}</span>
                        </label>
                    </div>
                    <div class="text-center font-weight-600 font-sm">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-black text-underline">
                                <span>{{ __('Forgot password?') }}</span>
                            </a>
                        @endif
                    </div>
                </div>
                <button type="submit" class="btn btn-dark text-white w-100 mt-3">{{ __('Sign in') }}</button>
            </form>
        </div>
    </div>
@endsection
