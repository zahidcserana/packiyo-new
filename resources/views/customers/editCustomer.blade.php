@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Customers',
        'subtitle' =>  __('Edit customer')
    ])
    <div class="container-fluid bg-lightGrey select2Container">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <a href="{{ route('customer.index') }}" class="text-black font-sm font-weight-600 d-inline-flex align-items-center bg-white border-8 px-3 py-2 mt-3">
                <i class="picon-arrow-backward-filled icon-lg icon-black mr-1"></i>
                {{ __('Back') }}
            </a>
        </div>
        <div class="row">
            <div class="col-xl-12 order-xl-1">
                <div class="card">
                    <div class="card-body">
                        <div class="nav-wrapper">
                            <ul class="nav nav-pills nav-fill flex-column flex-md-row" id="tabs-icons-text" role="tablist">
                                <li class="nav-item">
                                    <div class="nav-link mb-sm-3 mb-md-0 active" id="tabs-icons-text-1-tab"
                                       aria-controls="tabs-icons-text-1" aria-selected="true"><i class="ni ni-cloud-upload-96 mr-2"></i>{{ __('Customer') }}</div>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mb-sm-3 mb-md-0" id="tabs-icons-text-2-tab" href="{{ route('customer.editUsers', [ 'customer' => $customer ]) }}" role="tab" aria-controls="tabs-icons-text-2" aria-selected="false"><i class="ni ni-bell-55 mr-2"></i>{{ __('Users') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mb-sm-3 mb-md-0" id="tabs-icons-text-3-tab" href="{{ route('customers.easypost_credentials.index', [ 'customer' => $customer ]) }}" role="tab" aria-controls="tabs-icons-text-2" aria-controls="tabs-icons-text-3" aria-selected="false"><i class="ni ni-bell-55 mr-2"></i>{{__('Easypost Credentials')}}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mb-sm-3 mb-md-0" id="tabs-icons-text-3-tab" href="{{ route('customers.webshipper_credentials.index', [ 'customer' => $customer ]) }}" role="tab" aria-controls="tabs-icons-text-2" aria-controls="tabs-icons-text-3" aria-selected="false"><i class="ni ni-bell-55 mr-2"></i>{{__('Webshipper Credentials')}}</a>
                                </li>
                                @if($customer->parent && auth()->user()->isAdmin())
                                    <li class="nav-item">
                                        <a class="nav-link mb-sm-3 mb-md-0" id="tabs-icons-text-3-tab" href="{{ route('customers.rate_cards.edit', [ 'customer' => $customer ]) }}" role="tab" aria-controls="tabs-icons-text-2" aria-controls="tabs-icons-text-3" aria-selected="false"><i class="ni ni-bell-55 mr-2"></i>{{__('Rate cards')}}</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                        <div class="card shadow">
                            <div class="card-body">
                                <form method="post" id="create-customer-form" action="{{ route('customer.update', [ 'customer' => $customer ]) }}" autocomplete="off" enctype="multipart/form-data">
                                    @csrf
                                    <h6 class="heading-small text-muted mb-4">{{ __('Customer information') }}</h6>
                                    <div class="pl-lg-4">
                                        {{ method_field('PUT') }}
                                        @include('shared.forms.contactInformationFields', [
                                            'name' => 'contact_information',
                                            'contactInformation' => $customer->contactInformation,
                                            'customer' => $customer
                                        ])
                                        <hr>
                                        <div class="d-flex orderContactInfo flex-column mb-4">
                                            <div class="d-lg-flex">
                                                @include('shared.forms.select', [
                                                        'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_LOCALE,
                                                        'label' => __('Language'),
                                                        'containerClass' => 'w-50 mx-2',
                                                        'error' => ! empty($errors->get(\App\Models\CustomerSetting::CUSTOMER_SETTING_LOCALE)) ? $errors->first(\App\Models\CustomerSetting::CUSTOMER_SETTING_LOCALE) : false,
                                                        'value' => old('locale', $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_LOCALE] ?? ''),
                                                        'options' => [
                                                            'en' => __('English'),
                                                            'no' => __('Norwegian'),
                                                            'da' => __('Danish')
                                                        ]
                                                    ])

                                                @include('shared.forms.select', [
                                                       'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_CURRENCY,
                                                       'label' => __('Currency'),
                                                       'containerClass' => 'w-50 mx-2',
                                                       'value' => old('currency', $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_CURRENCY] ?? ''),
                                                       'options' => \App\Models\Currency::all()->pluck('code', 'id'),
                                                       'attributes' => [
                                                            'data-no-select2' => true,
                                                            'data-placeholder' => 'Currency'
                                                       ]
                                                    ])
                                            </div>
                                        </div>

                                        @include('shared.forms.productDimensionsFields', [
                                            'customer' => $customer
                                        ])

                                        @include('shared.forms.paperSizeFields', [
                                            'customer' => $customer
                                        ])

                                        <div class="flex-container">
                                            @include('shared.forms.input', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_HEADING,
                                                'label' => __('Packing / Order Slip Heading'),
                                                'containerClass' => 'w-100',
                                                'error' => ! empty($errors->get(\App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_HEADING)) ? $errors->first(\App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_HEADING) : false,
                                                'value' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_HEADING] ?? ''
                                            ])

                                            @include('shared.forms.textarea', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_TEXT,
                                                'label' => __('Packing / Order Slip Text'),
                                                'containerClass' => 'w-100',
                                                'class' => 'editor',
                                                'rows' => 5,
                                                'error' => ! empty($errors->get(\App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_TEXT)) ? $errors->first(\App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_TEXT) : false,
                                                'value' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_TEXT] ?? ''
                                            ])

                                            @include('shared.forms.textarea', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_FOOTER,
                                                'label' => __('Packing / Order Slip Footer'),
                                                'containerClass' => 'w-100',
                                                'class' => 'editor',
                                                'rows' => 5,
                                                'error' => ! empty($errors->get(\App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_FOOTER)) ? $errors->first(\App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_FOOTER) : false,
                                                'value' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_FOOTER] ?? ''
                                            ])

                                            <div class="form-group {{ $errors->has('order_slip_logo') ? 'has-danger' : '' }} flex-column col-6">
                                                <label class="form-control-label text-neutral-text-gray font-weight-600 font-xs" for="order_slip_logo">
                                                    {{ __('Order Slip Logo') }}
                                                </label>
                                                @include('shared.forms.dropzoneBasic', [
                                                   'url' => route('customer.update', [ 'customer' => $customer->id ]),
                                                   'images' => $customer->orderSlipLogo ?? '',
                                                   'name' => 'order_slip_logo'
                                               ])
                                            </div>

                                            @if($customer->allow_child_customers)
                                                <div class="form-group {{ $errors->has('threepl_logo') ? 'has-danger' : '' }} flex-column col-6">
                                                    <label class="form-control-label text-neutral-text-gray font-weight-600 font-xs" for="threepl_logo">
                                                        {{ __('3PL Logo') }}
                                                    </label>
                                                    @include('shared.forms.dropzoneBasic', [
                                                       'url' => route('customer.update', [ 'customer' => $customer->id ]),
                                                       'images' => $customer->threeplLogo ?? '',
                                                       'name' => 'threepl_logo'
                                                   ])
                                                </div>
                                            @endif

                                        @if($customer->availableShippingBoxes()->isNotEmpty())
                                        <div class="form-group">
                                            @include('shared.forms.select', [
                                               'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_SHIPPING_BOX_ID,
                                               'containerClass' => 'w-100',
                                               'label' => __('Shipping box'),
                                               'error' => ! empty($errors->get(\App\Models\CustomerSetting::CUSTOMER_SETTING_SHIPPING_BOX_ID)) ? $errors->first(\App\Models\CustomerSetting::CUSTOMER_SETTING_SHIPPING_BOX_ID) : false,
                                               'value' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_SHIPPING_BOX_ID] ?? '',
                                               'options' => $customer->availableShippingBoxes()->pluck('name', 'id')
                                            ])
                                        </div>
                                        @endif

                                        @if(count($customer->printers) > 0)
                                        <div class="form-group">
                                            @include('shared.forms.select', [
                                               'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_LABEL_PRINTER_ID,
                                               'containerClass' => 'w-100',
                                               'label' => __('Label printer'),
                                               'error' => ! empty($errors->get(\App\Models\CustomerSetting::CUSTOMER_SETTING_LABEL_PRINTER_ID)) ? $errors->first(\App\Models\CustomerSetting::CUSTOMER_SETTING_LABEL_PRINTER_ID) : false,
                                               'value' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_LABEL_PRINTER_ID] ?? '',
                                               'options' => [' ' => __('PDF')] + $customer->printers->pluck('hostnameAndName', 'id')->toArray()
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.select', [
                                               'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_BARCODE_PRINTER_ID,
                                               'containerClass' => 'w-100',
                                               'label' => __('Barcode printer'),
                                               'error' => ! empty($errors->get(\App\Models\CustomerSetting::CUSTOMER_SETTING_BARCODE_PRINTER_ID)) ? $errors->first(\App\Models\CustomerSetting::CUSTOMER_SETTING_BARCODE_PRINTER_ID) : false,
                                               'value' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_BARCODE_PRINTER_ID] ?? '',
                                               'options' => [' ' => __('PDF')] + $customer->printers->pluck('hostnameAndName', 'id')->toArray()
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.select', [
                                               'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_SLIP_PRINTER_ID,
                                               'containerClass' => 'w-100',
                                               'label' => __('Slip printer'),
                                               'error' => ! empty($errors->get(\App\Models\CustomerSetting::CUSTOMER_SETTING_SLIP_PRINTER_ID)) ? $errors->first(\App\Models\CustomerSetting::CUSTOMER_SETTING_SLIP_PRINTER_ID) : false,
                                               'value' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_SLIP_PRINTER_ID] ?? '',
                                               'options' => [' ' => __('PDF')] + $customer->printers->pluck('hostnameAndName', 'id')->toArray()
                                            ])
                                        </div>
                                        @endif

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_USE_ZPL_LABELS,
                                                'label' => ('Use ZPL Labels'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_USE_ZPL_LABELS] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_AUTO_PRINT,
                                                'label' => ('Auto Print Order Slip'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_AUTO_PRINT] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_PACKING_SLIP_IN_BULKSHIPPING,
                                                'label' => ('Have packing slips in Bulk shipping'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_PACKING_SLIP_IN_BULKSHIPPING] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_SHOW_PRICES_ON_SLIPS,
                                                'label' => ('Show prices on packing slip and order slip'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_SHOW_PRICES_ON_SLIPS] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_SHOW_SKUS_ON_SLIPS,
                                                'label' => __('Show Product SKU(s) on packing slip'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_SHOW_SKUS_ON_SLIPS] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_ORDERS,
                                                'label' => ('Disable autoload orders'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_ORDERS] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_PRODUCTS,
                                                'label' => ('Disable autoload products'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_PRODUCTS] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_PRODUCTS_ORDER_ITEMS,
                                                'label' => ('Disable autoload products - order items'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_PRODUCTS_ORDER_ITEMS] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_PRODUCTS_ORDERS_SHIPPED,
                                                'label' => ('Disable autoload products - orders shipped'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_PRODUCTS_ORDERS_SHIPPED] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_PRODUCTS_TOTE_ITEMS,
                                                'label' => ('Disable autoload products - tote items'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_PRODUCTS_TOTE_ITEMS] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_SINGLE_ORDER_PACKING,
                                                'label' => ('Disable autoload single order packing'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_SINGLE_ORDER_PACKING] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_INVENTORY_CHANGE_LOG,
                                                'label' => ('Disable autoload inventory change log'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_INVENTORY_CHANGE_LOG] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_SHIPMENT_REPORT,
                                                'label' => ('Disable autoload shipment report'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_SHIPMENT_REPORT] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_PICKER_REPORT,
                                                'label' => ('Disable autoload picker report'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_PICKER_REPORT] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_PACKER_REPORT,
                                                'label' => ('Disable autoload packer report'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_PACKER_REPORT] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_SHIPPED_ITEMS_REPORT,
                                                'label' => ('Disable autoload shipped items report'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_SHIPPED_ITEMS_REPORT] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_ALLOW_LOAD_BUTTON,
                                                'label' => ('Allow load page without search or filters'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_ALLOW_LOAD_BUTTON] ?? ''
                                            ])
                                        </div>

                                        @if($customer->parent_id)
                                            <div class="form-group">
                                                @include('shared.forms.checkbox', [
                                                    'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_ALLOW_CLIENT_VOID_LABEL,
                                                    'label' => __('Allow client to void labels'),
                                                    'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_ALLOW_CLIENT_VOID_LABEL] ?? ''
                                                ])
                                            </div>
                                        @endif

                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_ONLY_USE_BULK_SHIP_PICKABLE_LOCATIONS,
                                                'label' => ('Only use bulk ship pickable locations'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_ONLY_USE_BULK_SHIP_PICKABLE_LOCATIONS] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.input', [
                                                'label' => __('Contents type'),
                                                'containerClass' => 'w-100',
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_CONTENTS_TYPE,
                                                'value' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_CONTENTS_TYPE] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.input', [
                                                'label' => __('Customs description'),
                                                'containerClass' => 'w-100',
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_CUSTOMS_DESCRIPTION,
                                                'value' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_CUSTOMS_DESCRIPTION] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.input', [
                                                'label' => __('Customs signer'),
                                                'containerClass' => 'w-100',
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_CUSTOMS_SIGNER,
                                                'value' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_CUSTOMS_SIGNER] ?? ''
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.input', [
                                                'label' => __('EEL/PFC'),
                                                'containerClass' => 'w-100',
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_EEL_PFC,
                                                'value' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_EEL_PFC] ?? ''
                                            ])
                                        </div>

                                        @include('shared.forms.ajaxSelect', [
                                                'url' => route('shipping_method_mapping.filterShippingMethods', ['customer' => $customer->id]),
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_DEFAULT_RETURN_SHIPPING_METHOD,
                                                'className' => 'ajax-user-input enabled-for-customer shipping_method_id',
                                                'containerClass' => 'w-100',
                                                'placeholder' => __('Search'),
                                                'allowClear' => true,
                                                'label' => __('Return Shipping Method'),
                                                'default' => [
                                                    'id' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DEFAULT_RETURN_SHIPPING_METHOD] ?? '',
                                                    'text' => isset($settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DEFAULT_RETURN_SHIPPING_METHOD]) ? (\App\Models\ShippingMethod::find($settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DEFAULT_RETURN_SHIPPING_METHOD])->name ?? '') : ''
                                                ]
                                            ])
                                        @include('shared.forms.ajaxSelect', [
                                            'url' => route('purchase_order.filterWarehouses', ['customer' => $customer->id ?? 1]),
                                            'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_DEFAULT_WAREHOUSE,
                                            'className' => 'ajax-user-input enabled-for-customer',
                                            'containerClass' => 'w-100',
                                            'placeholder' => __('Search'),
                                            'allowClear' => true,
                                            'label' => __('Default Warehouse'),
                                            'default' => [
                                                'id' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DEFAULT_WAREHOUSE] ?? '',
                                                'text' => !empty($settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DEFAULT_WAREHOUSE]) ? (\App\Models\Warehouse::find($settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_DEFAULT_WAREHOUSE])->name ?? '') : ''
                                            ]
                                        ])
                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_AUTO_RETURN_LABEL,
                                                'label' => ('Create return labels while shipping'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_AUTO_RETURN_LABEL] ?? ''
                                            ])
                                        </div>
                                        <div class="form-group">
                                            @include('shared.forms.checkbox', [
                                                'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_SHIPPING_NOTIFICATIONS_FOR_MANUAL_ORDERS,
                                                'label' => __('Send shipping notifications for manually created orders'),
                                                'checked' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_SHIPPING_NOTIFICATIONS_FOR_MANUAL_ORDERS] ?? ''
                                            ])
                                        </div>
                                        @if(auth()->user()->isAdmin() && !$customer->parent)
                                            <div class="form-group">
                                                @include('shared.forms.checkbox', [
                                                    'name' => 'allow_child_customers',
                                                    'label' => ('Allow child customers'),
                                                    'className' => 'ajax-user-input parent_customer_id',
                                                    'checked' => $customer->allow_child_customers
                                                ])
                                            </div>
                                        @endif

                                        @if($customer->parent)
                                            <p>
                                                {{ __('Child customer of :customer', ['customer' => $customer->parent->contactInformation->name]) }}
                                            </p>
                                        @endif

                                        <div class="form-group">
                                            @include('shared.forms.select', [
                                               'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_PICKING_ROUTE_STRATEGY,
                                               'label' => __('Picking route strategy'),
                                               'value' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_PICKING_ROUTE_STRATEGY] ?? '',
                                               'options' => [
                                                   \App\Components\RouteOptimizationComponent::PICKING_STRATEGY_ALPHANUMERICALLY => __('Alphanumerically'),
                                                   \App\Components\RouteOptimizationComponent::PICKING_STRATEGY_MOST_INVENTORY => __('Most inventory'),
                                                   \App\Components\RouteOptimizationComponent::PICKING_STRATEGY_LEAST_INVENTORY => __('Least inventory'),
                                               ]
                                            ])
                                        </div>

                                        <div class="form-group">
                                            @include('shared.forms.select', [
                                               'name' => \App\Models\CustomerSetting::CUSTOMER_SETTING_LOT_PRIORITY,
                                               'label' => __('Lot priority'),
                                               'value' => $settings[\App\Models\CustomerSetting::CUSTOMER_SETTING_LOT_PRIORITY] ?? '',
                                               'options' => \App\Enums\LotPriority::translatedValues()
                                            ])
                                        </div>

                                        <div class="d-flex orderContactInfo flex-column mb-4">
                                            <div class="d-lg-flex">
                                                @include('shared.forms.select', [
                                                    'name' => 'ship_from_contact_information_id',
                                                    'containerClass' => 'w-50',
                                                    'label' => __('Default Ship From Address'),
                                                    'error' => ! empty($errors->get('ship_from_contact_information_id')) ? $errors->first('ship_from_contact_information_id') : false,
                                                    'value' => $customer->ship_from_contact_information_id,
                                                    'options' => ['none' => __('None')] + $addressBooks->pluck('information', 'contactInformation.id')->toArray(),
                                                ])
                                                @include('shared.forms.select', [
                                                    'name' => 'return_to_contact_information_id',
                                                    'containerClass' => 'w-50 mx-2',
                                                    'label' => __('Default Return To Address'),
                                                    'error' => ! empty($errors->get('return_to_contact_information_id')) ? $errors->first('return_to_contact_information_id') : false,
                                                    'value' => $customer->return_to_contact_information_id,
                                                    'options' => ['none' => __('None')] + $addressBooks->pluck('information', 'contactInformation.id')->toArray(),
                                                ])
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-center">
                                            <button
                                                id="submit-button"
                                                class="btn bg-logoOrange text-white mx-auto px-5 font-weight-700 mt-5">
                                                {{ __('Save') }}
                                            </button>
                                        </div>
                                    </div>
                                 </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script>
        window.ckeditor()
        new ImageDropzone('create-customer-form', 'submit-button');
    </script>
@endpush
