@include('shared.forms.input', [
    'name' => 'name',
    'label' => __('Name'),
    'value' => $rateCard->name ?? ''
])
