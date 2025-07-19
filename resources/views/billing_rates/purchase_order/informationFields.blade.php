
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
