@include('shared.forms.editSelectTag', [
    'containerClass' => 'form-group col-12 col-md-3',
    'labelClass' => '',
    'selectClass' => 'select-ajax-tags',
    'tags' => false,
    'label' => __('Tags'),
    'minimumInputLength' => 3
])
