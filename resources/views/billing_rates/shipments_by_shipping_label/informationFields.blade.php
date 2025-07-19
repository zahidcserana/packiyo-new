
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

    <div class="form-group generic_shipping_group">
        <input id="is_generic_shipping"
               type="checkbox"
               name="{{'settings[is_generic_shipping]'}}"
               value="1"
               @if(isset($settings) || old('settings'))
                   {{(old('settings.is_generic_shipping', $settings['is_generic_shipping'] ?? false) ) ? 'checked' : ''}}
               @endif
               class="{{$isReadonlyUser ?? '' ? ' disable-for-user-group' : '' }}"
        >
        <label id="generic_shipping_label" class="form-check-label{{$isReadonlyUser ?? '' ? ' disable-for-user-group' : '' }}" for="is_generic_shipping">
            {{__('Generic Shipping')}}
        </label>
    </div>

    <div class="table-responsive p-0">
        <input class="carriers_methods_selectables" type="hidden" name="settings[carriers_and_methods]" value="{{old('settings.carriers_and_methods', $settings['carriers_and_methods'] ?? '[]')}}">
        <input class="carriers_methods_sub_selectables" type="hidden" name="settings[methods_selected]" value="{{old('settings.methods_selected', $settings['methods_selected'] ?? '{}')}}">
        <label class="form-control-label" for="selectables-table">{{ __('Shipping Carriers') }}</label>
        <table class="table align-items-center table-hover col-12 p-0 selectables-table{{$isReadonlyUser ?? '' ? ' disable-for-user-group' : '' }}" style="width: 100% !important;" data-selectables="carriers_methods_selectables" data-sub-selectables="carriers_methods_sub_selectables" data-url="/billing_rates/carriers_and_methods">
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
        'label' => __('Percentage Fee - Charge a percentage of the label cost'),
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
                <h5 class="modal-title">{{ __('Carrier methods') }}</h5>
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
