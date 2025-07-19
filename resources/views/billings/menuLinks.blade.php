<div class="row mb-4">
    <div class="col col-12 mb-1">
        <div class="nav-wrapper p-0">
            <ul class="nav nav-pills nav-fill flex-md-row nav-bg-gray">
                <li class="nav-item">
                    <a class="nav-link mb-sm-3 mb-md-0 {{$active === 'customers' ? 'active' : ''}}" href="{{route('billings.customers')}}">
                        {{__('Customers')}}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link mb-sm-3 mb-md-0 {{$active === 'rate-cards' ? 'active' : ''}}" href="{{route('billings.rate_cards')}}">
                        {{__('Rate Cards')}}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link mb-sm-3 mb-md-0 {{$active === 'invoices' ? 'active' : ''}}" href="{{route('billings.invoices')}}">
                        {{__('Invoices')}}
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
