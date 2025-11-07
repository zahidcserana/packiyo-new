@extends('layouts.app')

@section('content')
    @component('layouts.headers.auth', [ 'title' => 'Inventory', 'subtitle' => 'Products'])
    @endcomponent
    @if($product->deleted_at)
        <div class="d-flex align-items-center justify-content-between mr-4 ml-4 mb-2">
            <span class="font-weight-400 d-inline-flex font-md text-red mt-3 ml-3">{{ __('Archived Product') }}</span>

            <span class="d-inline-flex align-items-center mt-3 mr-3">
                <button data-toggle="modal" data-id="{{ $product->id }}" data-target="#recover-product-modal" type="button" class="btn bg-logoOrange text-white mx-auto px-3 py-2 font-weight-700 border-8 recover-icon">{{ __('Un-Archive') }}</button>
            </span>
        </div>
        @include('shared.modals.components.product.recover')
    @endif
    <div class="container-fluid formsContainer">
        <div class="row px-3" id="globalForm" data-form-action="{{ route('product.update', ['product' => $product]) }}"  data-type="PUT">
            <form class="col-12 border-12 py-3 px-4 m-0 mb-3 bg-white smallForm productForm" action="{{ route('product.update', ['product' => $product]) }}"  data-type="PUT" enctype="multipart/form-data">
                <div class="border-bottom py-2 d-flex align-items-center">
                    <h6 class="modal-title text-black text-left"
                        id="modal-title-notification">{{ __('Product Details') }}</h6>
                    @include('shared.buttons.sectionEditButtons', [
                        'pd_class' => 'product_details_edit'
                    ])
                </div>
                @csrf
                {{ method_field('PUT') }}
                <input type="hidden" name="customer_id" value="{{ $product->customer_id }}" />
                <div class="d-flex text-center py-3 overflow-auto justify-content-between flex-column">
                    <div class="w-100">
                        <div class="d-flex justify-content-between border-bottom py-3 align-items-center">
                            <label for=""
                                   class="text-neutral-text-gray font-weight-600 font-xs" data-id="sku">{{ __('SKU') }}</label>
                            <input
                                class="form-control text-black text-right font-sm font-weight-600" name="sku" value="{{ $product->sku ?? "" }}">
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-3  align-items-center">
                            <label for=""
                                   class="text-neutral-text-gray font-weight-600 font-xs" data-id="name">{{ __('Name') }}</label>
                            <input
                                class="form-control text-black text-right font-sm font-weight-600" name="name" value="{{ $product->name ?? "-" }}">
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-3  align-items-center">
                            <label for="" class="text-neutral-text-gray font-weight-600 font-xs" data-id="barcode">{{ __('Barcode (UPC or other)') }}
                                <a href="{{ route('product.barcode', ['product' => $product]) }}" target="_blank" class="table-icon-button">
                                    <i class="picon-printer-light icon-lg align-middle"></i>
                                </a>
                            </label>
                            <input
                                class="form-control text-black text-right font-sm font-weight-600" name="barcode" value="{{ $product->barcode ?? "" }}">
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-3  align-items-center">
                            <label for=""
                                   class="text-neutral-text-gray font-weight-600 font-xs" data-id="price">{{ __('Price') }}</label>
                            <input
                                class="form-control text-black text-right font-sm font-weight-600" name="price" value="{{ $product->price ?? "" }}">
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-3  align-items-center">
                            <label for=""
                                   class="text-neutral-text-gray font-weight-600 font-xs" data-id="cost">{{ __('Cost') }}</label>
                            <input
                                class="form-control text-black text-right font-sm font-weight-600" name="cost" value="{{ $product->cost ?? "" }}">
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-3  align-items-center">
                            <label for=""
                                   class="text-neutral-text-gray font-weight-600 font-xs" data-id="weight">{{ __('Weight (' . \App\Models\Customer::WEIGHT_UNITS[customer_settings($product->customer->id, 'weight_unit', \App\Models\Customer::WEIGHT_UNIT_DEFAULT)] . ')') }}</label>
                            <input
                                class="form-control text-black text-right font-sm font-weight-600" name="weight" value="{{ $product->weight ?? "" }}">
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-3  align-items-center">
                            <label for=""
                                   class="text-neutral-text-gray font-weight-600 font-xs" data-id="width">{{ __('Width (' . \App\Models\Customer::DIMENSION_UNITS[customer_settings($product->customer->id, 'dimensions_unit', \App\Models\Customer::DIMENSION_UNIT_DEFAULT)] . ')') }}</label>
                            <input
                                class="form-control text-black text-right font-sm font-weight-600" name="width" value="{{ $product->width ?? "" }}">
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-3  align-items-center">
                            <label for=""
                                   class="text-neutral-text-gray font-weight-600 font-xs" data-id="height">{{ __('Height (' . \App\Models\Customer::DIMENSION_UNITS[customer_settings($product->customer->id, 'dimensions_unit', \App\Models\Customer::DIMENSION_UNIT_DEFAULT)] . ')') }}</label>
                            <input
                                class="form-control text-black text-right font-sm font-weight-600" name="height" value="{{ $product->height ?? "" }}">
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-3  align-items-center">
                            <label for=""
                                   class="text-neutral-text-gray font-weight-600 font-xs" data-id="sku">{{ __('Length (' . \App\Models\Customer::DIMENSION_UNITS[customer_settings($product->customer->id, 'dimensions_unit', \App\Models\Customer::DIMENSION_UNIT_DEFAULT)] . ')') }}</label>
                            <input
                                class="form-control text-black text-right font-sm font-weight-600" name="length" value="{{ $product->length ?? "" }}">
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-3  align-items-center">
                            <label for=""
                                   class="text-neutral-text-gray font-weight-600 font-xs" data-id="value">{{ __('Replacement Value' ) }}
                            </label>
                            <input
                                class="form-control text-black text-right font-sm font-weight-600" name="value" value="{{ $product->value ?? "" }}">
                        </div>

                        <div class="d-flex justify-content-between border-bottom py-3  align-items-center">
                            <label for="" class="text-neutral-text-gray font-weight-600 font-xs" data-id="reorder_threshold">{{ __('Reorder threshold') }}</label>
                            <input class="form-control text-black text-right font-sm font-weight-600" name="reorder_threshold" value="{{ $product->reorder_threshold ?? "" }}">
                        </div>

                        <div class="d-flex justify-content-between border-bottom py-3  align-items-center">
                            <label for="" class="text-neutral-text-gray font-weight-600 font-xs" data-id="quantity_reorder">{{ __('Quantity reorder') }}</label>
                            <input class="form-control text-black text-right font-sm font-weight-600" name="quantity_reorder" value="{{ $product->quantity_reorder ?? "" }}">
                        </div>

                        @if($warehouses->isEmpty() || \Laravel\Pennant\Feature::for('instance')->inactive(\App\Features\MultiWarehouse::class))
                        <div class="d-flex justify-content-between border-bottom py-3 align-items-center">
                            <label for="quantity_reserved" class="text-neutral-text-gray font-weight-600 font-xs" data-id="quantity_reserved">{{ __('Quantity reserved') }}</label>
                            <input class="form-control text-black text-right font-sm font-weight-600" name="quantity_reserved" value="{{ $product->quantity_reserved ?? 0 }}">
                        </div>
                        @else
                            @foreach($warehouses as $warehouse)
                                <div class="d-flex justify-content-between border-bottom py-3  align-items-center">
                                    <label for="quantity_reserved[{{ $warehouse->id }}]" class="text-neutral-text-gray font-weight-600 font-xs" data-id="quantity_reserved[{{ $warehouse->id }}]">{{ __('Quantity reserved (Warehouse ' . $warehouse->contactInformation->name . ')') }}</label>
                                    <input class="form-control text-black text-right font-sm font-weight-600" name="quantity_reserved[{{ $warehouse->id }}]" value="{{ \App\Models\ProductWarehouse::whereWarehouseId($warehouse->id)->where('product_id', $product->id)->first()->quantity_reserved ?? 0 }}">
                                </div>
                            @endforeach
                        @endif

                        <div class="d-flex justify-content-between border-bottom py-3  align-items-center">
                            <label for="" class="text-neutral-text-gray font-weight-600 font-xs" data-id="tags">{{ __('Tags') }}</label>
                            <div class="input-container text-right">
                                @include('shared.forms.editSelectTag', [
                                    'labelClass' => '',
                                    'selectClass' => 'select-ajax-tags',
                                    'selectId' => '',
                                    'label' => '',
                                    'minimumInputLength' => 3,
                                    'default' => $product->tags
                                ])
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center border-bottom py-3 searchSelect editSelect">
                            <label for="type" class="text-neutral-text-gray font-weight-600 font-xs" data-id="type">
                                {{ __('Product type') }}
                            </label>
                            <div class="input-container text-right font-weight-600 text-black">
                                <select
                                    class="form-control type-select"
                                    type="text"
                                    name="type"
                                    id="type"
                                >
                                    @foreach(\App\Models\Product::PRODUCT_TYPES as $key => $type)
                                        @if($product->type === $key)
                                            <option value="{{ $key }}" selected>{{ __($type) }}</option>
                                        @else
                                            <option value="{{ $key }}">{{ __($type) }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center border-bottom py-3 searchSelect editSelect">
                            <label for="hazmat" class="text-neutral-text-gray font-weight-600 font-xs" data-id="hazmat">
                                {{ __('Hazmat') }}
                            </label>
                            <div class="input-container text-right font-weight-600 text-black">
                                <select
                                    class="form-control type-select"
                                    type="text"
                                    name="hazmat"
                                    id="hazmat"
                                >
                                    <option value="0" {{ empty($product->hazmat) ? 'selected' : null }} >{{ __('Not set') }}</option>
                                    @foreach(\App\Models\Product::HAZMAT_OPTIONS as $option => $name)
                                        <option value="{{ $option }}" {{ $product->hazmat === $option ? 'selected' : ''}}>{{ __($name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="py-3 border-bottom d-flex">
                            <div class="w-100 product-details-checkboxes-title">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-neutral-text-gray font-weight-600 font-xs">{{ __('Priority Counting') }}</div>
                                    <div class="text-black text-right font-sm font-weight-600 priority-counting-status">{{ $product->priority_counting_requested_at !== null ? __('Yes') : __('No') }}</div>
                                </div>
                            </div>
                            <div class="custom-form-checkbox priority-counting-checkbox position-relative font-weight-600 d-none">
                                @include('shared.forms.checkbox', [
                                   'name' => 'priority_counting_requested_at',
                                   'label' => __('Priority Counting'),
                                   'checked' => $product->priority_counting_requested_at,
                                   'value' => true
                                ])
                            </div>
                        </div>
                        <div class="py-3 border-bottom d-flex hidden_checkboxes">
                            <div class="w-100 product-details-checkboxes-title">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-neutral-text-gray font-weight-600 font-xs">{{ __('Needs Serial Number') }}</div>
                                    <div class="text-black text-right font-sm font-weight-600 serial-number-status">{{ $product->has_serial_number === 1 ? "Yes" : "No" }}</div>
                                </div>
                            </div>
                            <div class="custom-form-checkbox serial-number-checkbox position-relative font-weight-600 d-none">
                                @include('shared.forms.checkbox', [
                                   'name' => 'has_serial_number',
                                   'label' => __('Needs Serial Number'),
                                   'checked' => $product->has_serial_number,
                                   'value' => true
                                ])
                            </div>
                        </div>
                        @if(!$product->isKit())
                            <div class="py-3 border-bottom d-flex hidden_checkboxes">
                                <div class="w-100 product-details-checkboxes-title">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-neutral-text-gray font-weight-600 font-xs">{{__('Needs Lot Tracking')}}:</div>
                                        <div class="text-black text-right font-sm font-weight-600 lot-tracking-status">{{ $product->lot_tracking == 1 ? "Yes" : "No" }}</div>
                                    </div>
                                </div>
                                <div class="custom-form-checkbox serial-number-checkbox position-relative font-weight-600 d-none">
                                    @include('shared.forms.checkbox', [
                                       'name' => 'lot_tracking',
                                       'label' => __('Needs Lot Tracking'),
                                       'checked' => $product->lot_tracking,
                                       'value' => true
                                    ])
                                </div>
                            </div>

                            <div
                                class="{{$product->lot_tracking != 1 ? 'd-none' :'d-flex'}} justify-content-between align-items-center border-bottom py-3 searchSelect editSelect"
                                id="lot_priority_container"
                            >
                                <label for="" class="text-neutral-text-gray font-weight-600 font-xs" data-id="lot_tracking">{{ __('Lot Priority') }}</label>
                                <div class="input-container text-right font-weight-600 text-black">
                                    @include('shared.forms.select', [
                                       'name' => 'lot_priority',
                                       'containerClass' => '',
                                       'class'=> 'form-control type-select',
                                       'value' => old('lot_priority', $product->lot_priority),
                                       'options' => $lotPriorityRules,
                                    ])
                                </div>
                            </div>
                        @endif
                        <div class="py-3 border-bottom d-flex hidden_checkboxes">
                            <div class="w-100 product-details-checkboxes-title">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-neutral-text-gray font-weight-600 font-xs">{{ __('Featured Product') }}</div>
                                    <div class="text-black text-right font-sm font-weight-600 inventory-sync-status">{{ $product->inventory_sync === 1 ? "Yes" : "No" }}</div>
                                </div>
                            </div>
                            <div class="custom-form-checkbox serial-number-checkbox position-relative font-weight-600 d-none">
                                @include('shared.forms.checkbox', [
                                   'name' => 'inventory_sync',
                                   'label' => __('Featured Product'),
                                   'checked' => $product->inventory_sync,
                                   'value' => true
                                ])
                            </div>
                        </div>
                    </div>
                    @if(!app('user')->isClientCustomer())
                        <div class="py-3 border-bottom d-flex">
                            <div class="w-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-neutral-text-gray font-weight-600 font-xs">{{ __('Client name') }}</div>
                                    <div class="text-black text-right font-sm font-weight-600">{{ $product->customer->contactInformation->name }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </form>

            <form class="col-12 border-12 py-3 px-4 m-0 mb-3 bg-white smallForm productForm" data-type="PUT" enctype="multipart/form-data" action="{{ route('product.update', ['product' => $product]) }}">
                <div class="border-bottom  py-2 d-flex">
                    <h6 class="modal-title text-black text-left" id="modal-title-notification">{{ __('Product Barcodes') }}</h6>
                    @include('shared.buttons.sectionEditButtons', ['saveButtonId' => 'submit-barcodes'])
                    @csrf
                    {{ method_field('PUT') }}
                </div>
                <div class="suppliersPreview">
                    <h6 class="heading-small text-muted my-3">{{ __('Barcodes') }}</h6>
                    <div class="table-responsive table-overflow">
                        <table class="table align-items-center col-12 items-table font-sm barcode-table">
                            <thead>
                                <tr class="text-black">
                                    <th>{{ __('Barcode') }}</th>
                                    <th>{{ __('Quantity') }}</th>
                                    <th>{{ __('Description') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(!empty($product->productBarcodes) && count($product->productBarcodes))
                                    @foreach($product->productBarcodes as $productBarcode)
                                        <tr data-id="{{ $productBarcode->id }}">
                                            <td>{{ $productBarcode->barcode }}</td>
                                            <td>{{ $productBarcode->quantity }}</td>
                                            <td>{{ $productBarcode->description }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="3" class="text-center">No information</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="d-flex text-center py-3 overflow-auto justify-content-between suppliersEdit">
                    <div class="w-100 ">
                        <div class="form-group mb-0 text-left mb-3">
                            <div class="editContainer">
                                <div class="table-responsive barcode_container">
                                    <h6 class="heading-small text-muted mb-4 mx-4">{{ __('Barcodes') }}</h6>
                                    <table class="col-12 table align-items-center table-flush barcode-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Barcode') }}</th>
                                                <th>{{ __('Quantity') }}</th>
                                                <th>{{ __('Description') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="barcode_container">
                                            @if(!empty($product->productBarcodes) && count($product->productBarcodes))
                                                @foreach($product->productBarcodes as $productBarcode)
                                                        <tr>
                                                            <td style="white-space: unset">
                                                                @include('shared.forms.input', [
                                                                    'name' => 'product_barcodes[barcode][]',
                                                                    'label' => '',
                                                                    'value' => $productBarcode->barcode ?? '',
                                                                    'class' => 'p-2 prevent-submit-on-enter'
                                                                ])
                                                            </td>
                                                            <td>
                                                                @include('shared.forms.input', [
                                                                    'name' => 'product_barcodes[quantity][]',
                                                                    'label' => '',
                                                                    'value' => $productBarcode->quantity ?? '',
                                                                ])
                                                            </td>
                                                            <td>
                                                                @include('shared.forms.input', [
                                                                    'name' => 'product_barcodes[description][]',
                                                                    'label' => '',
                                                                    'value' => $productBarcode->description ?? '',
                                                                ])
                                                            </td>
                                                            <td class="delete-supplier-row delete-action">
                                                                <div><i class="fas fa-trash-alt text-lightGrey"></i></div>
                                                            </td>
                                                        </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td>
                                                        @include('shared.forms.input', [
                                                            'name' => 'product_barcodes[barcode][]',
                                                            'label' => '',
                                                        ])
                                                    </td>
                                                    <td>
                                                        @include('shared.forms.input', [
                                                            'name' => 'product_barcodes[quantity][]',
                                                            'label' => '',
                                                        ])
                                                    </td>
                                                    <td>
                                                        @include('shared.forms.input', [
                                                            'name' => 'product_barcodes[description][]',
                                                            'label' => '',
                                                        ])
                                                    </td>
                                                    <td class="delete-row">
                                                        <div><i class="fas fa-trash-alt text-lightGrey"></i></div>
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                    <div class="d-flex justify-content-center mx-3">
                                        <button type="button" class="btn bg-logoOrange text-white w-100 my-4 font-weight-700 fa fa-plus mt-3 w-100 border-12" id="add-barcode"></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <form class="col-12 border-12 py-3 px-4 m-0 mb-3 bg-white smallForm productForm" data-type="PUT" enctype="multipart/form-data" action="{{ route('product.update', ['product' => $product]) }}">
                <div class="border-bottom  py-2 d-flex">
                    <h6 class="modal-title text-black text-left"
                        id="modal-title-notification">{{ __('International Orders') }}</h6>
                    @include('shared.buttons.sectionEditButtons', ['saveButtonId' => 'submit-international'])
                    @csrf
                    {{ method_field('PUT') }}
                </div>
                <div id="international_orders"
                     class="d-flex py-3 overflow-auto justify-content-between">
                    <div class="w-100">
                        <div class="d-flex justify-content-between border-bottom py-3  align-items-center">
                            <label for=""
                                   class="text-neutral-text-gray font-weight-600 font-xs" data-id="customs_price">{{ __('Customs Price (' . (customer_settings($product->customer->id, 'currency') ?? 'USD') . ')') }}</label>
                            <input class="form-control text-black text-right font-sm font-weight-600" name="customs_price" value="{{ $product->customs_price ?? "" }}">
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-bottom py-3 searchSelect editSelect">
                            <label for="" class="text-neutral-text-gray font-weight-600 font-xs" data-id="customs_price">
                                {{ __('Country of origin') }}
                            </label>
                            <div class="input-container text-right font-weight-600 text-black">
                                @include('shared.forms.countrySelect', [
                                    'label' => '',
                                    'name' => 'country_of_origin',
                                    'value' => $product->country_of_origin,
                                    'class' => 'editable-input',
                                    'dropdownParent' => '#international_orders',
                                ])
                            </div>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-3  align-items-center">
                            <label for=""
                                   class="text-neutral-text-gray font-weight-600 font-xs" data-id="hs_code">{{ __('HS Code') }}</label>
                            <input
                                class="form-control text-black text-right font-sm font-weight-600"
                                placeholder="{{ __('HS Code') }}"
                                type="text"
                                name="hs_code"
                                value="{{ $product->hs_code ?? '' }}">
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-3 align-items-center">
                            <label for="customs_description" class="text-neutral-text-gray font-weight-600 font-xs" data-id="customs_description">{{ __('Customs Description') }}</label>
                            <input class="form-control text-black text-right font-sm font-weight-600" name="customs_description" value="{{ $product->customs_description ?? "" }}">
                        </div>
                    </div>
                </div>
            </form>

            <form class="col-12 border-12 py-3 px-4 m-0 mb-3 bg-white productForm smallForm {{ !$product->isKit() && (!isset($sessionCustomer) || !isset($sessionCustomer->parent_id)) ? '' : 'd-none' }}" data-type="PUT" enctype="multipart/form-data" action="{{ route('product.update', ['product' => $product]) }}" id="locations-form">
                <div class="border-bottom py-2 d-flex">
                    <h6 class="modal-title text-black text-left"
                        id="modal-title-notification">{{ __('Product Locations') }}</h6>
                    @include('shared.buttons.sectionEditButtons', ['saveButtonId' => 'submit-international'])
                    @csrf
                    {{ method_field('PUT') }}
                    <input type="hidden" name="product_locations[]" value="">
                </div>
                <div class="productLocationsTable">
                    <div id="addLocationBlock"
                         data-product="{{ $product->id }}"
                         data-url="{{ route('location.filterLocations') . '?product_id=' . $product->id }}"
                         data-lot-url="{{ route('lot.filterLots') . '?product_id=' . $product->id }}"
                         class="table-responsive px-0"
                         data-placeholder="{{  __('Search for a location') }}"
                         data-lot-placeholder="{{  __('Search for a lot') }}"
                    >
                        <div class="select-tabs d-flex text-center py-3 overflow-auto justify-content-between">
                            <x-datatable
                                table-id="product-locations-table"
                                container-class="fullTable"
                                datatableOrder="{!! json_encode($datatableProductLocationsOrder) !!}"
                                filters=""
                                :search=false
                                :columns=true
                            />
                        </div>
                        @if(empty($product->locations) || (! empty($product->locations) && ! count($product->locations)))
                            <div class="text-center text-black font-weight-600 py-5 noInfo">
                                {{ __('No Information') }}
                            </div>
                        @endif
                        <div class="d-flex justify-content-right addLocation">
                            <!--add_location_item-->
                            <button type="button" class="btn bg-logoOrange px-lg-5 text-white" id="delete-empty-locations">
                                {{__('Remove empty locations')}}
                            </button>
                            <button type="button" class="btn bg-logoOrange px-lg-5 text-white" id="add-new-location">
                                {{__('Add locations')}}
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <form class="col-12 border-12 py-3 px-4 m-0 mb-3 bg-white smallForm productForm" data-type="PUT" enctype="multipart/form-data" action="{{ route('product.update', ['product' => $product]) }}">
                <div class="border-bottom  py-2 d-flex">
                    <h6 class="modal-title text-black text-left"
                        id="modal-title-notification">{{ __('Notes') }}</h6>
                    @include('shared.buttons.sectionEditButtons', ['saveButtonId' => 'submit-international'])
                    @csrf
                    {{ method_field('PUT') }}
                </div>
                <div class="notes-data m-3">
                    <span class="text-neutral-text-gray font-weight-600 font-xs">
                        {{ strip_tags($product->notes) }}
                    </span>
                </div>
                <div class="form-group mx-2 mb-3 text-left d-none notes-input">
                    <textarea name="notes" class="form-control">{{ strip_tags($product->notes) }}</textarea>
                </div>
            </form>

            <form class="col-12 border-12 py-3 px-4 m-0 mb-3 bg-white smallForm productForm" data-type="PUT" enctype="multipart/form-data" action="{{ route('product.update', ['product' => $product]) }}">
                <div class="border-bottom  py-2 d-flex">
                    <h6 class="modal-title text-black text-left"
                        id="modal-title-notification">{{ __('Vendor Information') }}</h6>
                    @include('shared.buttons.sectionEditButtons', ['saveButtonId' => 'submit-vendors'])
                    @csrf
                    {{ method_field('PUT') }}
                    <input type="hidden" name="update_vendor" value="true">
                </div>
                <div class="suppliersPreview">
                    <h6 class="heading-small text-muted my-3">{{ __('Suppliers') }}</h6>
                    <div class="table-responsive table-overflow">
                        <table class="table align-items-center col-12 items-table font-sm {{ (!empty($product->suppliers) && count($product->suppliers)) ? '' : 'd-none' }}">
                            <thead>
                            <tr class="text-black">
                                <td>{{ __('Name') }}</td>
                                <td>{{ __('Email') }}</td>
                                <td>{{ __('Zip') }}</td>
                                <td>{{ __('City') }}</td>
                                <td>{{ __('Phone') }}</td>
                            </tr>
                            </thead>
                            <tbody>
                            @if(!empty($product->suppliers) && count($product->suppliers))
                                @foreach($product->suppliers as $supplier)
                                    <tr data-id="{{ $supplier->id }}">
                                        <td>{{ $supplier->contactInformation->name }}</td>
                                        <td>{{ $supplier->contactInformation->email }}</td>
                                        <td>{{ $supplier->contactInformation->zip }}</td>
                                        <td>{{ $supplier->contactInformation->city }}</td>
                                        <td>{{ $supplier->contactInformation->phone }}</td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 empty {{ (!empty($product->suppliers) && count($product->suppliers)) ? 'd-none' : '' }}">
                        <p class="text-center">{{ __('No information') }}</p>
                    </div>
                </div>
                <div class="d-flex text-center py-3 overflow-auto justify-content-between suppliersEdit">
                    <div class="w-100 ">
                        <div class="form-group mb-0 text-left mb-3">
                            <div class="editContainer">
                                <div class="table-responsive supplier_container">
                                    <h6 class="heading-small text-muted mb-4 mx-4">{{ __('Suppliers') }}</h6>
                                    <table class="col-12 table align-items-center table-flush">
                                        <tbody id="supplier_container"
                                               data-className="ajax-user-input"
                                               data-url="{{ route('product.filterSuppliers') }}"
                                               data-placeholder="{{ __('Search') }}">
                                        @if(!empty($product->suppliers) && count($product->suppliers))
                                            @foreach($product->suppliers as $supplier)
                                                <tr>
                                                    <td style="white-space: unset">
                                                        @include('shared.forms.ajaxSelect', [
                                                            'url' => route('product.filterSuppliers') . '?product_id=' . $product->id,
                                                            'name' => 'suppliers[]',
                                                            'className' => 'ajax-user-input getFilteredSuppliers',
                                                            'placeholder' => __('Search Supplier'),
                                                            'labelClass' => 'd-none',
                                                            'minInputLength' => 0,
                                                            'containerClass' => 'mb-0',
                                                            'label' => '',
                                                            'default' => [
                                                                'id' => $supplier->id,
                                                                'text' => $supplier->contactInformation->name
                                                            ]
                                                        ])
                                                    </td>
                                                    <td class="delete-supplier-row delete-action">
                                                        <div><i class="fas fa-trash-alt text-lightGrey"></i></div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td style="white-space: unset">
                                                    @include('shared.forms.ajaxSelect', [
                                                        'url' => route('product.filterSuppliers') . '?product_id=' . $product->id,
                                                        'name' => 'suppliers[]',
                                                        'className' => 'ajax-user-input getFilteredSuppliers',
                                                        'placeholder' => __('Search Supplier'),
                                                        'labelClass' => 'd-none',
                                                        'minInputLength' => 0,
                                                        'containerClass' => 'mb-0',
                                                        'label' => ''
                                                    ])
                                                </td>
                                                <td class="delete-row">
                                                    <div><i class="fas fa-trash-alt text-lightGrey"></i></div>
                                                </td>
                                            </tr>
                                        @endif
                                        </tbody>
                                    </table>
                                    <div class="d-flex justify-content-center mx-3">
                                        <button type="button" class="btn bg-logoOrange text-white w-100 my-4 font-weight-700 fa fa-plus mt-3 w-100 border-12" id="add_item"></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <form  class="col-12 border-12 py-3 px-4 m-0 mb-3 bg-white smallForm productForm" id="product-form" action="{{ route('product.update', ['product' => $product]) }}"  data-type="PUT" enctype="multipart/form-data">
                <div class="border-bottom  py-2 d-flex">
                    <h6 class="modal-title text-black text-left"
                        id="modal-title-notification">{{ __('Product Images') }}</h6>
                    @include('shared.buttons.sectionEditButtons', ['saveButtonId' => 'submit-button'])
                    @csrf
                    {{ method_field('PUT') }}
                </div>
                <div class="mt-3">
                    <div class="d-flex overflow-scroll-x" id="detailsImageContainer">
                        @if(!empty($product->productImages) && count($product->productImages))
                            @foreach($product->productImages as $image)
                                <a href="#" title="{{ __('Show image') }}" data-toggle="modal" data-target="#big-image-modal" data-image="{{ $image->source }}">
                                    <img src="{{ $image->source }}" class="detailsImage mr-2"  alt=""/>
                                </a>
                            @endforeach
                        @else
                            <p class="text-center w-100">{{ __('No images') }}</p>
                        @endif
                    </div>
                </div>

                <div class="productEditDropzone w-100 p-2">
                    <label for="" class="text-neutral-text-gray font-weight-600 font-xs" data-id="file"></label>
                        @include('shared.forms.dropzoneBasic', [
                            'url' => route('product.update', ['product' => $product]),
                            'images' => $product->productImages,
                            'name' => 'file',
                            'isMultiple' => true
                       ])
                </div>
            </form>

            <div class="col-12 border-12 p-0 m-0 mb-3 bg-white">
                <div class="py-3 px-4">
                    <div class="border-bottom  py-2 d-flex">
                        <h6 class="modal-title text-black text-left" id="modal-title-notification">
                            {{ __('Order Items Listed') }}
                        </h6>
                    </div>
                    <div class="select-tabs d-flex text-center py-3 overflow-auto justify-content-between">
                        <x-datatable
                            search-placeholder="{{ __('Search product orders') }}"
                            table-id="product-order-items-table"
                            container-class="fullTable"
                            datatableOrder="{!! json_encode($datatableOrder) !!}"
                            disable-autoload="{{ (bool) customer_settings(app('user')->getSessionCustomer()->id ?? null, \App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_PRODUCTS_ORDER_ITEMS, 0) }}"
                            disable-autoload-text="{{ __('Search for specific data') }}"
                            disable-autoload-button-label="{{ __('Load entire section') }}"
                            disable-autoload-allow-load-button="true"
                        />
                    </div>
                </div>
            </div>
            <div class="col-12 border-12 p-0 m-0 mb-3 bg-white">
                <div class="py-3 px-4">
                    <div class="border-bottom  py-2 d-flex">
                        <h6 class="modal-title text-black text-left" id="modal-title-notification">
                            {{ __('Order Shipped') }}
                        </h6>
                    </div>
                    <div class="select-tabs d-flex text-center py-3 overflow-auto justify-content-between">
                        <x-datatable
                            search-placeholder="{{ __('Search shipped items') }}"
                            table-id="product-shipped-items-table"
                            container-class="fullTable"
                            datatableOrder="{!! json_encode($datatableShippedItemsOrder) !!}"
                            disable-autoload="{{ (bool) customer_settings(app('user')->getSessionCustomer()->id ?? null, \App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_PRODUCTS_ORDERS_SHIPPED, 0) }}"
                            disable-autoload-text="{{ __('Search for specific data') }}"
                            disable-autoload-button-label="{{ __('Load entire section') }}"
                            disable-autoload-allow-load-button="true"
                        />
                    </div>
                </div>
            </div>
            <div class="col-12 border-12 p-0 m-0 mb-3 bg-white">
                <div class="py-3 px-4">
                    <div class="border-bottom  py-2 d-flex">
                        <h6 class="modal-title text-black text-left" id="modal-title-notification">
                            {{ __('Tote Items') }}
                        </h6>
                    </div>
                    <div class="select-tabs d-flex text-center py-3 overflow-auto justify-content-between">
                        <x-datatable
                            search-placeholder="{{ __('Search tote items') }}"
                            table-id="totes-item-table"
                            container-class="fullTable"
                            datatableOrder="{!! json_encode($datatableToteOrder) !!}"
                            disable-autoload="{{ (bool) customer_settings(app('user')->getSessionCustomer()->id ?? null, \App\Models\CustomerSetting::CUSTOMER_SETTING_DISABLE_AUTOLOAD_PRODUCTS_TOTE_ITEMS, 0) }}"
                            disable-autoload-text="{{ __('Search for specific data') }}"
                            disable-autoload-button-label="{{ __('Load entire section') }}"
                            disable-autoload-allow-load-button="true"
                        />
                    </div>
                </div>
            </div>
            <form class="col-12 border-12 py-3 px-4 m-0 mb-3 bg-white productForm {{ !$product->isKit() ? 'd-none' : ''}}" action="{{ route('product.update', ['product' => $product]) }}"  data-type="PUT" enctype="multipart/form-data" id="kits-form">
                @csrf
                <div class="col-12 border-12 p-0 m-0 mb-3 bg-white">
                    <div class="py-3 px-4">
                        <div class="border-bottom  py-2 d-flex kit-title-icons">
                            <h6 class="modal-title text-black text-left" id="modal-title-notification">
                                {{ __('Component SKU(s)') }}
                            </h6>
                            @include('shared.buttons.sectionEditButtons', [
                                'saveButtonId' => 'submit-kit',
                                'editButton' => 'edit-kit-content'
                                ])
                            {{ method_field('PUT') }}
                        </div>
                        <div class="select-tabs d-flex text-center py-3 overflow-auto justify-content-between">
                            <x-datatable
                                search-placeholder="{{ __('Search kit items') }}"
                                table-id="product-kits-table"
                                container-class="fullTable"
                                datatableOrder="{!! json_encode($datatableKitsOrder) !!}"
                            >
                            </x-datatable>
                        </div>
                        <div class="p-2 d-none" id="edit-kit-items">
                            @include('shared.forms.kitProductInput', [
                                'url' => route('product.filterKitProducts', $product->id),
                                'className' => 'ajax-user-input sendFilteredRequest',
                                'placeholder' => 'Search',
                                'label1' => ('Product'),
                                'label2' => __('Quantity'),
                                'visible' => ! empty($product) &&  count($product->kitItems),
                                'defaults' => $product->kitItems ?? ''
                            ])
                            @include('shared.forms.checkbox', [
                                'name' => 'update_orders',
                                'label' => __('Update pending orders'),
                                'checked' => false,
                            ])
                        </div>
                    </div>
                </div>
            </form>

            <div class="col-12 border-12 p-0 m-0 mb-3 bg-white overflow-hidden productForm">
                <div class="has-scrollbar py-3 px-4">
                    <div class="border-bottom  py-2 d-flex">
                        <h6 class="modal-title text-black text-left"
                            id="modal-title-notification">{{ __('Product Log') }}</h6>
                    </div>
                    <div class="select-tabs d-flex py-3 overflow-auto justify-content-between">
                        <div class="w-100">
                            <x-datatable
                                search-placeholder="{{ __('Search event') }}"
                                table-id="audit-log-table"
                                model-name="Product"
                                datatableOrder="{!! json_encode($datatableAuditOrder) !!}"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <button class="globalSave p-0 border-0 bg-logoOrange align-items-center" id="{{ $saveButtonId ?? '' }}" type="button">
                <i class="picon-save-light icon-white icon-xl" title="Save"></i>
            </button>
        </div>
    </div>
    @include('shared.modals.components.product.transferToLocation', compact('product'))
    @include('shared.modals.components.product.addToLocation', compact('product'))
    @include('shared.modals.components.product.change_lot', compact('product'))
    @include('shared.modals.components.product.image')
    @include('shared.modals.components.product.importCsv')
    @include('shared.modals.components.product.exportCsv')
    @include('shared.modals.productKit')
@endsection

@push('js')
    <script>
        var parentId = {{$product->id}}
    </script>
    <script>
        window.productData = {!! json_encode($product) !!}
        window.excludedSuppliersIds = @json($product->suppliers ? $product->suppliers->pluck('id') : []);
        $(document).ready(function () {
            $('#tabsSelection').on('change', function () {
                let $tab = $(this).find("option:selected").data('href');
                $('.select-tabs').addClass('d-none')
                $('#' + $tab).removeClass('d-none')
            }).select2()
        })
    </script>
    <script>
        new Product({!! $product->id !!}, '{{$product->lot_tracking}}')
        new ImageDropzone('product-form', 'submit-button');
    </script>
@endpush
