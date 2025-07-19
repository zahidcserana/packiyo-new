@include('shared.forms.checkbox', [
    'name' => 'is_enabled',
    'label' => __('Active'),
    'value' => 1,
    'checked' => $billingRate['is_enabled'] ?? true
])
@include('shared.forms.input', [
    'name' => 'name',
    'label' => __('Name - Internal reference only'),
    'value' => old('name', $billingRate['name'] ?? '')
])
@include('shared.forms.input', [
    'name' => 'code',
    'label' => __('Invoice Code'),
    'value' => $billingRate['code'] ?? '',
])

<div class="form-group">
    <input id="if_no_other_rate_applies"
           type="checkbox"
           name="{{'settings[if_no_other_rate_applies]'}}"
           value="1"
    @if(isset($settings) || old('settings'))
        {{(old('settings.if_no_other_rate_applies', $settings['if_no_other_rate_applies'] ?? false) ) ? 'checked' : ''}}
        @endif
    >
    <label class="form-check-label" for="if_no_other_rate_applies">
        {{__('If no other rate of this type applies…')}}
    </label>
</div>

<div class="form-group">
    <div class="select-tags">
        <label class="text-neutral-text-gray font-weight-600 font-xs" data-id="tags">{{ __('Product has tags') }}</label>
        <select name="settings[match_has_product_tag][]" class="custom-select select-ajax-tags"
                data-ajax--url="{{ route('tag.filterInputTags') }}"
                data-minimum-input-length="3"
                multiple="multiple"
        >
            @if(!empty($settings['match_has_product_tag']))
                @foreach ($settings['match_has_product_tag'] as $tag)
                    <option selected value="{{ $tag }}">{{ $tag }}</option>
                @endforeach
            @endif
        </select>
    </div>
</div>

<div class="form-group">
    <div class="select-tags">
        <label class="text-neutral-text-gray font-weight-600 font-xs" data-id="tags">{{ __('Product does not have tags') }}</label>
        <select name="settings[match_has_not_product_tag][]" class="custom-select select-ajax-tags"
                data-ajax--url="{{ route('tag.filterInputTags') }}"
                data-minimum-input-length="3"
                multiple="multiple"
        >
            @if(!empty($settings['match_has_not_product_tag']))
                @foreach ($settings['match_has_not_product_tag'] as $tag)
                    <option selected value="{{ $tag }}">{{ $tag }}</option>
                @endforeach
            @endif
        </select>
    </div>
</div>

<div class="form-group">
    <div class="select-tags">
        <label class="text-neutral-text-gray font-weight-600 font-xs" data-id="tags">{{ __('Order has tags') }}</label>
        <select name="settings[match_has_order_tag][]" class="custom-select select-ajax-tags"
                data-ajax--url="{{ route('tag.filterInputTags') }}"
                data-minimum-input-length="3"
                multiple="multiple"
        >
            @if(!empty($settings['match_has_order_tag']))
                @foreach ($settings['match_has_order_tag'] as $tag)
                    <option selected value="{{ $tag }}">{{ $tag }}</option>
                @endforeach
            @endif
        </select>
    </div>
</div>

<div class="form-group">
    <div class="select-tags">
        <label class="text-neutral-text-gray font-weight-600 font-xs" data-id="tags">{{ __('Order does not have tags') }}</label>
        <select name="settings[match_has_not_order_tag][]" class="custom-select select-ajax-tags"
                data-ajax--url="{{ route('tag.filterInputTags') }}"
                data-minimum-input-length="3"
                multiple="multiple"
        >
            @if(!empty($settings['match_has_not_order_tag']))
                @foreach ($settings['match_has_not_order_tag'] as $tag)
                    <option selected value="{{ $tag }}">{{ $tag }}</option>
                @endforeach
            @endif
        </select>
    </div>
</div>

@include('shared.forms.input', [
    'name' => 'settings[flat_fee]',
    'label' => __('Base order price'),
    'value' => old('settings[flat_fee]', $billingRate['settings']['flat_fee'] ?? 0),
    'step' => '0.01'
])

<div class="form-group">
    <input
        id="charge_flat_fee"
        type="checkbox"
        name="{{'settings[charge_flat_fee]'}}"
        value="1"
    @if(isset($settings) || old('settings'))
        {{old('settings.charge_flat_fee', $settings['charge_flat_fee'] ?? false) ? 'checked' : ''}}
        @endif
    >
    <label class="form-check-label" for="charge_flat_fee">
        {{__('Use base order price')}}
    </label>
</div>

<h4 class="text-neutral-text-gray">{{'Fees'}}</h4>

<div class="table-responsive p-0 border rounded mb-4">
    <table class="table items-table align-items-center">
        <thead >
            <tr>
                <th scope="col">{{ __('From/To') }}</th>
                <th scope="col">{{ __('Rate') }}</th>
            </tr>
        </thead>
        <tbody id="item_container">
            <tr>
                <td>
                    {{__('First item')}}
                </td>
                <td>
                    @include('shared.forms.input', [
                        'name' => 'settings[first_pick_fee]',
                        'type' => 'number',
                        'label' => '',
                        'value' => old('settings.first_pick_fee', $settings['first_pick_fee'] ?? 0),
                        'class' => '',
                        'containerClass' => 'm-0',
                        'labelClass' => 'd-none',
                        'step' => '0.01'
                    ])
                </td>
            </tr>
            <tr>
                <td>
                    <div class="form-group m-0">
                        <input
                            id="charge_additional_sku_picks"
                            type="checkbox"
                            name="{{'settings[charge_additional_sku_picks]'}}"
                            value="1"
                        @if(isset($settings) || old('settings'))
                            {{old('settings.charge_additional_sku_picks', $settings['charge_additional_sku_picks'] ?? false) ? 'checked' : ''}}
                            @endif
                        >
                        <label class="form-check-label" for="charge_additional_sku_picks">
                            {{__('1st item of additional SKUs')}}
                        </label>
                    </div>
                </td>
                <td>
                    @include('shared.forms.input', [
                        'name' => 'settings[additional_sku_pick_fee]',
                        'label' => '',
                        'value' => old('settings.additional_sku_pick_fee', $settings['additional_sku_pick_fee'] ?? 0),
                        'type' => 'number',
                        'containerClass' => 'm-0',
                        'labelClass' => 'd-none',
                        'step' => '0.01'
                    ])
                </td>
            </tr>
            @if(count( old('settings.pick_range_fees', [])) > 0)
                @foreach(old('settings.pick_range_fees') as $key => $item)
                    @if(isset($item['to']))
                        @include('billing_rates.shipments_by_picking_rate_v2.itemRateLine')
                    @endif
                @endforeach
            @else
                @foreach($settings['pick_range_fees'] ?? [] as $key => $item)
                    @if(isset($item['to']))
                        @include('billing_rates.shipments_by_picking_rate_v2.itemRateLine')
                    @endif
                @endforeach
                @if(!isset($settings['pick_range_fees']))
                    @include('billing_rates.shipments_by_picking_rate_v2.itemRateLineBlank')
                @endif
            @endif
        </tbody>
    </table>
    @if(count( old('settings.pick_range_fees', [])) <= 0 && !isset($settings['pick_range_fees']))
        <div class="row border-top">
            <div class="col col-12 d-flex justify-content-center">
                <a class="add_rate btn bg-logoOrange text-white my-4">{{__('Add Fee')}}</a>
            </div>
        </div>
    @endif
</div>

<div class="form-group">
    <label class="text-neutral-text-gray font-weight-600 font-xs" data-id="tags">{{ __('Rest of the items') }}</label>
    <input
        class="form-control"
        name="settings[remaining_picks_fee]"
        value="{{old('settings.remaining_picks_fee', $settings['remaining_picks_fee'] ?? 0)}}"
        type="number"
        step="0.01"
    >
</div>

@push('js')
    <script>
        new BillingRateCheckForDuplicateRates();
    </script>
@endpush
