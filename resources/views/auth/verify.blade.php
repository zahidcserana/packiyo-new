@extends('auth.container')

@section('container.content')
    <div style="max-width: 600px;" class="card m-0 w-100">
        <div class="card-header">
            <h1 class="m-0 font-md">{{ __('Verify Your Email Address') }}</h1>
        </div>
        <div class="card-body">
            @if (session('resent'))
                <div class="alert alert-success-orange" role="alert">
                    {{ __('A fresh verification link has been sent to your email address.') }}
                </div>
            @endif

            {{ __('Before proceeding, please check your email for a verification link.') }}

            @if (Route::has('verification.resend'))
                {{ __('If you did not receive the email') }}, <a href="{{ route('verification.resend') }}">{{ __('click here to request another') }}</a>
            @endif
        </div>
    </div>
@endsection


