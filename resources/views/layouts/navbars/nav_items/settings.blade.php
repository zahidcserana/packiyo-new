@php
$settingMenuPageViewNames = (!empty($page) &&
    (
        in_array($page, [
            'user_settings.edit',
            'settings.manageUsers',
            'order_status.index',
            'order_status.edit',
            'order_status.create',
            'location_type.index',
            'location_type.create',
            'shipping_box.index',
            'shipping_box.create',
            'shipping_box.edit',
            'picking_carts.index',
            'picking_carts.create',
            'picking_carts.edit',
            'tote.index',
            'tote.create',
            'tote.edit',
            'kits.index',
            'shipping_method_mapping.index',
            'shipping_method_mapping.create',
            'shipping_method_mapping.edit',
            'shipping_method.index',
            'shipping_method.edit',
            'location.index',
            'location.create',
            'return_status.index',
            'supplier.index',
            'supplier.edit',
            'address_book.index',
            'transfer_orders.index'
        ])
    )
);
@endphp
<li class="nav-item mr-4">
    <div class="dropup dropup-left">
        <a href="#" class="nav-link dropdown-toggle {{ $settingMenuPageViewNames ? 'active_button' : '' }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="picon-settings-light icon-lg"></i>
        </a>
        <div class="dropdown-menu sidebar-dropdown-menu position-absolute">
            <ul class="p-0">
                @if (menu_item_visible('shipping_carrier.index'))
                <li class="nav-item">
                    <a href="{{ route('shipping_carrier.index') }}" class="dropdown-item {{ (!empty($page) && in_array($page, ['shipping_carrier.index', 'shipping_carrier.edit'])) ? 'active_item' : '' }}"><span class="nav-link-text">{{ __('Shipping Carriers') }}</span></a>
                </li>
                @endif
                @if (menu_item_visible('shipping_method_mapping.index'))
                <li class="nav-item">
                    <a href="{{ route('shipping_method_mapping.index') }}" class="dropdown-item {{ (!empty($page) && in_array($page, ['shipping_method_mapping.index', 'shipping_method_mapping.create', 'shipping_method_mapping.edit'])) ? 'active_item' : '' }}"><span class="nav-link-text">{{ __('Shipping Mappings') }}</span></a>
                </li>
                @endif
                @if (menu_item_visible('shipping_method.index'))
                <li class="nav-item">
                    <a href="{{ route('shipping_method.index') }}" class="dropdown-item {{ (!empty($page) && in_array($page, ['shipping_method.index', 'shipping_method.edit'])) ? 'active_item' : '' }}"><span class="nav-link-text">{{ __('Shipping Services') }}</span></a>
                </li>
                @endif
                @if (menu_item_visible('shipping_box.index'))
                <li class="nav-item">
                    <a href="{{ route('shipping_box.index') }}" class="dropdown-item {{ (!empty($page) && in_array($page, ['shipping_box.index', 'shipping_box.create', 'shipping_box.edit'])) ? 'active_item' : '' }}"><span class="nav-link-text">{{ __('Packaging') }}</span></a>
                </li>
                @endif
                @if (menu_item_visible('shipping_box-picking_carts-tote-location-location_type'))
                <li class="nav-item">
                    <a href="{{ route('location.index') }}" class="dropdown-item {{ (! empty($page) && in_array($page, ['location.index', 'location.create'])) ? 'active_item' : '' }} openCreateModal"><span class="nav-link-text">{{ __('Locations') }}</span></a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('location_type.index') }}" class="dropdown-item {{ (! empty($page) && in_array($page, ['location_type.index', 'location_type.create'])) ? 'active_item' : '' }}"><span class="nav-link-text">{{ __('Location Types') }}</span></a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('tote.index') }}" class="dropdown-item {{ (!empty($page) && in_array($page, ['tote.index', 'tote.create', 'tote.edit'])) ? 'active_item' : '' }}"><span class="nav-link-text">{{ __('Picking Totes') }}</span></a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('transfer_orders.index') }}" class="dropdown-item {{ (!empty($page) && $page === 'transfer_orders.index') ? 'active_item' : '' }}"><span class="nav-link-text">{{ __('Transfer Orders') }}</span></a>
                </li>
                @endif
                @if (menu_item_visible('settings.manageUsers'))
                <li class="nav-item">
                    <a href="{{ route('settings.manageUsers') }}" class="dropdown-item {{ (!empty($page) && $page === 'settings.manageUsers') ? 'active_item' : '' }}"><span class="nav-link-text">{{ __('User Accounts') }}</span></a>
                </li>
                @endif
                @if (menu_item_visible('user_settings.edit'))
                <li class="nav-item">
                    <a href="{{ route('user_settings.edit') }}" class="dropdown-item {{ (!empty($page) && $page === 'user_settings.edit') ? 'active_item' : '' }}"><span class="nav-link-text">{{ __('Timezone & Printing') }}</span></a>
                </li>
                @endif
                @if (menu_item_visible('printer.index'))
                <li class="nav-item">
                    <a href="{{ route('printer.index') }}" class="dropdown-item"><span class="nav-link-text">{{ __('Connected Printers') }}</span></a>
                </li>
                @endif
                @if (menu_item_visible('warehouses.index'))
                <li class="nav-item">
                    <a href="{{ route('warehouses.index') }}" class="dropdown-item {{ (! empty($page) && in_array($page, ['warehouses.index', 'warehouses.create', 'warehouses.edit'])) ? 'active_item' : '' }} openCreateModal"><span class="nav-link-text">{{ __('Warehouses') }}</span></a>
                </li>
                @endif
                @if (menu_item_visible('kits.index'))
                <li class="nav-item">
                    <a href="{{ route('kits.index') }}" class="dropdown-item {{ (!empty($page) && $page === 'kits.index') ? 'active_item' : '' }}"><span class="nav-link-text">{{ __('Manage Kits') }}</span></a>
                </li>
                @endif
                @if (menu_item_visible('supplier.index'))
                <li class="nav-item">
                    <a href="{{ route('supplier.index') }}" class="dropdown-item {{ (! empty($page) && in_array($page, ['supplier.index'])) ? 'active_item' : '' }}"><span class="nav-link-text">{{ __('Manage Vendors') }}</span></a>
                </li>
                @endif
                @if (menu_item_visible('return_status.index'))
                    <li class="nav-item">
                        <a href="{{ route('return_status.index') }}" class="dropdown-item {{ (! empty($page) && in_array($page, ['return_status.index'])) ? 'active_item' : '' }}"><span class="nav-link-text">{{ __('Return Status') }}</span></a>
                    </li>
                @endif
                @if ($threePLCustomer && $threePLCustomer->hasFeature('App\Features\ThreePLBilling'))
                <li class="nav-item">
                    <a href="{{ route('billings.index') }}" class="dropdown-item"><span class="nav-link-text">{{ __('Billing') }}</span></a>
                </li>
                @endif
                @if (menu_item_visible('address_book.index'))
                <li class="nav-item">
                    <a href="{{ route('address_book.index') }}" class="dropdown-item {{ (! empty($page) && in_array($page, ['address_book.index'])) ? 'active_item' : '' }}"><span class="nav-link-text">{{ __('Address Book') }}</span></a>
                </li>
                @endif
                @if(Feature::for('instance')->active(\App\Features\SelfService::class) && empty(request()->get('legacysettings')))
                    <li class="nav-item">
                        <a href="/app/settings/" class="dropdown-item"><span class="nav-link-text">{{ __('Settings') }}</span></a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</li>
