
<div>
    @include('shared.forms.checkbox', [
        'name' => 'is_enabled',
        'label' => __('Active'),
        'value' => 1,
        'checked' => $billingRate['is_enabled'] ?? true
    ])
    @include('shared.forms.input', [
        'name' => 'name',
        'label' => __('Name - Internal reference only'),
        'value' => $billingRate['name'] ?? ''
    ])
    @include('shared.forms.input', [
        'name' => 'code',
        'label' => __('Invoice Code - Displayed on the invoice'),
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
               class="{{$isReadonlyUser ?? '' ? ' disable-for-user-group' : '' }}"
        >
        <label class="form-check-label{{$isReadonlyUser ?? '' ? ' disable-for-user-group' : '' }}" for="if_no_other_rate_applies">
            {{__('If no other rate of this type applies…')}}
        </label>
    </div>

    <div class="form-group custom_packaging_group">
        <input id="is_custom_packaging"
               type="checkbox"
               name="{{'settings[is_custom_packaging]'}}"
               value="1"
               @if(isset($settings) || old('settings'))
                   {{(old('settings.is_custom_packaging', $settings['is_custom_packaging'] ?? false) ) ? 'checked' : ''}}
               @endif
               class="{{$isReadonlyUser ?? '' ? ' disable-for-user-group' : '' }}"
        >
        <label id="custom_packaging_label" class="form-check-label{{$isReadonlyUser ?? '' ? ' disable-for-user-group' : '' }}" for="is_custom_packaging">
            {{__('Custom Package')}}
        </label>
    </div>

<!--    TABLE for shipping boxes-->
    <div class="table-responsive p-0">
        <input class="customer_selectables" type="hidden" name="settings[customer_selected]"
               value="{{old('settings.customer_selected', $settings['customer_selected'] ?? '[]')}}">
        <input class="shipping_boxes_selectables" type="hidden" name="settings[shipping_boxes_selected]"
               value="{{old('settings.shipping_boxes_selected', $settings['shipping_boxes_selected'] ?? '{}')}}">
        <label class="form-control-label" for="selectables-table">{{ __('Selected Package(s)') }}</label>
        <table class="table align-items-center table-hover col-12 p-0
        selectables-table{{$isReadonlyUser ?? '' ? ' disable-for-user-group' : '' }}"
               style="width: 100% !important;"
               data-selectables="customer_selectables"
               data-sub-selectables="shipping_boxes_selectables"
               data-url="/billing_rates/customers_and_shipping_boxes">
            <thead class="thead-light"></thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="form-group">
        <div class="select-tags">
            <label class="text-neutral-text-gray font-weight-600 font-xs" data-id="tags">{{ __('If Order is Tagged') }}</label>
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
            <label class="text-neutral-text-gray font-weight-600 font-xs" data-id="tags">{{ __('If Order is Not Tagged') }}</label>
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

    <div class="form-group">
        <input
            id="charge_flat_fee"
            type="checkbox"
            name="{{'settings[charge_flat_fee]'}}"
            value="1"
            @if(isset($settings) || old('settings'))
                {{old('settings.charge_flat_fee', $settings['charge_flat_fee'] ?? false) ? 'checked' : ''}}
            @endif
            class="{{$isReadonlyUser ?? '' ? ' disable-for-user-group' : '' }}"
        >
        <label class="form-check-label{{$isReadonlyUser ?? '' ? ' disable-for-user-group' : '' }}" for="custom_boxes">
            {{__('Include flat fee charge')}}
        </label>
    </div>
    @include('shared.forms.input', [
        'name' => 'settings[flat_fee]',
        'label' => __('Flat Fee - Charge a fixed amount'),
        'value' => $settings['flat_fee'] ?? 0,
        'type' => 'number',
        'step' => '0.01'
    ])
    @include('shared.forms.input', [
        'name' => 'settings[percentage_of_cost]',
        'label' => __('Percentage Fee - Charge a percentage of the box cost'),
        'value' => $settings['percentage_of_cost'] ?? 0,
        'max' => 1000,
        'type' => 'number',
        'step' => '0.00001'
    ])
</div>
<div class="modal fade confirm-dialog" id="selectables-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Shipping Boxes') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul id="table-column-name-list" class="list-group"></ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

@push('js')
    <script>
         let rateId = '{{$billingRate->id ?? 0}}';
         new BillingRateCheckForDuplicateRates();
    </script>
@endpush
