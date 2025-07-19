@php
    $inboundMenuPageViewNames = (!empty($page) &&
    (
        in_array($page, [
            'purchase_orders.index',
            'purchase_orders.edit',
            'transfer_orders.index'
        ])
    )
);
@endphp
<li class="nav-item">
    <a href="#inbound" class="nav-link"  data-toggle="collapse" role="button" aria-expanded="{{ $inboundMenuPageViewNames ? 'true' : 'false' }}" aria-controls="inbound">
        <i class="picon-basket-light icon-lg"></i>
        <span class="nav-link-text">{{ __('Inbound') }}</span>
    </a>
    <div class="collapse {{ $inboundMenuPageViewNames ? 'show' : '' }}" id="inbound">
        <ul class="nav nav-sm flex-column">
            @if (menu_item_visible('purchase_orders.index'))
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('purchase_orders.index') }}">
                        <span class="nav-link-text {{ (!empty($page) && ($page == 'purchase_orders.index')) ? 'active_item' : '' }}">{{ __('Purchase Orders') }}</span>
                    </a>
                </li>
            @endif
            @if (menu_item_visible('transfer_orders.index'))
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('transfer_orders.index') }}">
                        <span class="nav-link-text {{ (!empty($page) && ($page == 'transfer_orders.index')) ? 'active_item' : '' }}">{{ __('Transfer Orders') }}</span>
                    </a>
                </li>
            @endif
        </ul>
    </div>
</li>
