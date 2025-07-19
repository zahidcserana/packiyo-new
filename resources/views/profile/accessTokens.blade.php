<h6 class="heading-small text-muted mb-4">{{ __('Access Tokens') }}</h6>

<x-toastr type="error" key="not_allow_password" />

@if (session('access_token_status'))
<div class="alert alert-success-orange m-4" role="alert">
    {{ session('access_token_status') }}
</div>
@endif

<table class="table">
<thead>
<tr>
    <th>{{ __('Name') }}</th>
    <th>{{ __('Creation date') }}</th>
    <th>{{ __('Last used date') }}</th>
    <th>&nbsp;</th>
</tr>
</thead>
<tbody>
    @foreach (auth()->user()->tokens as $token)
        <tr>
            <td>{{ $token->name }}</td>
            <td>{{ user_date_time($token->created_at) }}</td>
            <td>{{ $token->last_used_at ? user_date_time($token->last_used_at, true) : __('Never') }}</td>
            <td>
                <form action="{{ route('profile.delete_access_token', ['token' => $token]) }}" method="post" style="display: inline-block">
                    @csrf
                    @method('delete')
                    <button class="btn btn-outline-danger" type="submit" data-confirm-message="{{ __('Are you sure you want to delete this token?') }}">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </td>
        </tr>
    @endforeach
</tbody>
</table>

<h6 class="heading-small text-muted my-4">{{ __('Create new token') }}</h6>

<form method="post" action="{{ route('profile.create_access_token') }}">
@csrf
<div class="form-group">
    <label class="form-control-label" for="access_token_name">{{ __('Token name') }}</label>
    <input type="text" class="form-control" name="name" id="access_token_name" />
</div>

<button type="submit" class="btn bg-logoOrange mx-auto px-5 font-weight-700 mt-5 mb-2 change-tab text-white d-block">{{ __('Save') }}</button>
</form>

@if (app('user')->getSelectedCustomers()->count() == 1)
    @php
        $customer = app('user')->getSelectedCustomers()->first()
    @endphp
    <p>
        {{ __('Connection information') }}<br />
        {{ __('URL:') }} {{ request()->root() }}/api/v1<br />
        {{ __('Tenant name:') }} {{ \Illuminate\Support\Str::of(request()->getHost())->explode('.')->first() }}<br />
        {{ __('Customer ID:') }} {{ $customer->id }}
    </p>
@endif
