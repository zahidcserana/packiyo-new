
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
        'name' => 'settings[description]',
        'label' => __('Description - This is for internal reference only'),
        'value' => $settings['description'] ?? ''
    ])
    @include('shared.forms.input', [
        'name' => 'code',
        'label' => __('Invoice Code - Displayed on invoice'),
        'value' => $billingRate['code'] ?? '',
    ])
    <div class="form-group{{$isReadonlyUser ?? '' ? ' disable-for-user-group' : '' }}">
        <label class="form-control-label">{{ __('Charged By - Select how this rate is applied') }}</label>
        <select name="{{'settings[unit]'}}" class="form-control form-control-sm{{$isReadonlyUser ?? '' ? ' disable-for-user-group' : '' }}" data-toggle="select" data-placeholder="">
            @foreach(\App\Models\BillingRate::AD_HOC_UNITS as $unit)
                <option value="{{$unit}}"
                @if(isset($settings) || old('settings'))
                    {{$unit === old('settings.unit', $settings['unit'] ?? false) ? 'selected' : ''}}
                @endif
                >
                    {{$unit}}
                </option>
            @endforeach
        </select>
    </div>
    @include('shared.forms.input', [
        'name' => 'settings[fee]',
        'label' => __('Fee - Amount charged'),
        'value' => $settings['fee'] ?? 0,
        'type' => 'number',
        'step' => '0.01'
    ])
</div>
@push('js')
    <script>
        new BillingRateCheckForDuplicateRates();
    </script>
@endpush
