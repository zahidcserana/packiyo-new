<ul class="submenu">
    <li>
        <ul>
            <li class="nav-item">
                <a href="{{ route('billings.customers') }}" class="nav-link {{ current_page(route('billings.customers')) }}">
                    <span class="nav-link-text">{{ __('Billing Customers') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('billings.rate_cards') }}" class="nav-link {{ current_page(route('billings.rate_cards')) }}">
                    <span class="nav-link-text">{{ __('Rate Cards') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('billings.invoices') }}" class="nav-link {{ current_page(route('billings.invoices')) }}">
                    <span class="nav-link-text">{{ __('Invoices') }}</span>
                </a>
            </li>
        </ul>
    </li>
</ul>
