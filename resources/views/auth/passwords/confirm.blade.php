@extends('auth.container')

@section('container.content')
    <div style="max-width: 600px;" class="card m-0 w-100">
        <div class="card-header">
            <h1 class="m-0 font-md">{{ __('Confirm Password') }}</h1>
        </div>
        <div class="card-body">
            <form method="POST" class="loginForm m-0" action="{{ route('password.confirm') }}">
                @csrf
                <div class="form-group mb-0">
                    <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('Password') }}</label>
                    <div class="col-md-6">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-dark text-white w-100 my-3">{{ __('Confirm Password') }}</button>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-gray-dark d-flex align-items-center justify-content-start">
                        <i class="picon-arrow-backward-light mr-2"></i>{{ __('Forgot Your Password?') }}
                    </a>
                @endif
            </form>
        </div>
    </div>
@endsection
