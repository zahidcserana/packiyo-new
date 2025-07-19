<li class="submenu {{ $class ?? '' }}" data-submenu="billings.menu">
    <ul>
        <li class="nav-item d-none">
            <a href="{{ route('invoices.index') }}" class="nav-link">
                <i class="nc-icon nc-single-folded-content text-gray"></i>
                <span class="nav-link-text text-gray">{{ __('Manage Invoices') }}</span>
            </a>
        </li>
        <li class="nav-item d-none">
            <a href="{{ route('invoice_statuses.index') }}" class="nav-link">
                <i class="nc-icon nc-single-folded-content text-gray"></i>
                <span class="nav-link-text text-gray">{{ __('Invoice Statuses') }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('rate_cards.index') }}" class="nav-link">
                <i class="nc-icon nc-single-folded-content text-gray"></i>
                <span class="nav-link-text text-gray">{{ __('Rate Cards') }}</span>
            </a>
        </li>
    </ul>
</li>
