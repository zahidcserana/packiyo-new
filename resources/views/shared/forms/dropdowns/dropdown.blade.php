<div class="nav-item dropdown">
    @if (app()->user->getSortedCustomers()->count() > 1)
        <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            {{ isset($sessionCustomer) ? $sessionCustomer->contactInformation->name : __('All') }}
        </button>
        <div class="dropdown-menu customers-menu" aria-labelledby="dropdownMenuButton" style="overflow: auto; max-height: 60vh">
            @if(auth()->user()->isAdmin() && !auth()->user()->customers->count())
                <a class="dropdown-item parent-customer-dropdown-item" href="{{ route('user.removeSessionCustomer') }}">{{ __('All') }}</a>
            @endif
            @foreach (app()->user->getSortedCustomers() as $customer)
                <a class="dropdown-item {{$customer->parent_id ? 'customer-dropdown-item' : 'parent-customer-dropdown-item'}}" href="{{ route('user.setSessionCustomer', ['customer' => $customer->id])}}">{{$customer->parent_id ? '-' : ''}} {{ $customer->contactInformation->name ?? '' }}</a>
            @endforeach
        </div>
    @endif
</div>

