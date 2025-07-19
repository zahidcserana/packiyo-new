@php
    $configurations = $orderChannelDetails['data']['source_connections'][0]['configuration'] ?? [];
@endphp

<div class="card p-4 strech-container">
    <div class="border-bottom  py-2 d-flex">
        <h6 class="modal-title text-black text-left">
            {{ __('Manage Configurations - Order Channel') }}
        </h6>
    </div>
    <div class="pt-3">
        @if(!is_null($orderChannelInfo) && $orderChannelInfo['success'] && $orderChannelInfo['data']['key_value_info'])
            @foreach ($orderChannelInfo['data']['key_value_info'] as $keyValue)    
                @include('shared.forms.input', [
                    'label' => $keyValue['key'],
                    'containerClass' => 'w-100',
                    'name' => "",
                    'value' => $keyValue['value'] ?? '',
                    'readOnly' => 'readonly',
                    'class' => 'bg-white'
                ])
            @endforeach
        @endif
        @if(collect($configurations)->where('editable', 1)->count() > 0)
        <form method="post" action="{{ route('order_channels.updateSourceConfiguration', [ 'orderChannel' => $orderChannel ]) }}" autocomplete="off" enctype="multipart/form-data">
            @csrf
            @foreach($configurations as $key => $configuration)
                @if($configuration['connection_oriented'] && $configuration['editable'] && $configuration['type'] != 'CHECKBOX')
                    <input type="hidden" name="configurations[{{$key}}][field]" value="{{$configuration['field']}}">
                    <div class="form-group">
                        @if($configuration['type'] == 'DROPDOWN')
                            @include('shared.forms.select', [
                                'label' => $configuration['title'],
                                'containerClass' => 'w-100 text-capitalize',
                                'class' => 'text-capitalize w-100',
                                'name' => "configurations[$key][value]",
                                'value' => $configuration['value'] ?? $configuration['default_value'] ?? '',
                                'options' => $configuration['options'] ?? [],
                                'placeholder' => 'Select ' . $configuration['title']
                            ])
                        @else
                            @include($configuration['type'] == 'TEXTAREA' ? 'shared.forms.textarea' : 'shared.forms.input', [
                                'label' => $configuration['title'],
                                'containerClass' => 'w-100',
                                'name' => "configurations[$key][value]",
                                'value' => $configuration['value'] ?? ''
                            ])
                        @endif
                    </div>
                @endif
                @if($configuration['connection_oriented'] && $configuration['type'] == 'CHECKBOX')
                    <input type="hidden" name="configurations[{{$key}}][field]" value="{{$configuration['field']}}">
                    <div class="form-group">
                        @include('shared.forms.checkbox', [
                            'name' => "configurations[$key][value]",
                            'label' => $configuration['title'],
                            'checked' => $configuration['value'] ?? ''
                        ])
                    </div>
                @endif
            @endforeach
            <div class="d-flex justify-content-center">
                <button type="submit" class="btn bg-logoOrange mx-auto px-5 font-weight-700 mt-5 change-tab text-white">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
        @endif
    </div>
</div>
