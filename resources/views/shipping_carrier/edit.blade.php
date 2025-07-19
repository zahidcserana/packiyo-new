@extends('layouts.app')
@section('content')
    @component('layouts.headers.auth', [ 'title' => __('Shipping Carriers'), 'subtitle' => __('Edit'), 'buttons' => [['title' => __('Back to list'), 'href' => route('shipping_carrier.index')]]])
    @endcomponent
    <div class="container-fluid hasTagging">
        <div class="row">
            <div class="col-12 taggingBlock mb-3">

            </div>
            <div class="col-12">
                <div class="card">
                    <div class="table-responsive p-4">
                        <form method="post" id="shippingCarrierForm" action="{{ route('shipping_carrier.update', ['shipping_carrier' => $shippingCarrier, 'id' => $shippingCarrier->id]) }}" autocomplete="off">
                            @csrf
                            @method('PUT')
                            <div class="pl-lg-4">
                                <div class="d-lg-flex">
                                    <div class="form-group mb-0 my-3 mx-2 text-left  d-flex flex-column justify-content-end w-100">
                                        <label class="form-control-label text-neutral-text-gray font-weight-600 font-xs " for="carrier_name">Carrier</label>
                                        <div class=" shadow-none input-group input-group-alternative input-group-merge bg-white font-sm">
                                            <input type="text" name="carrier_name" class="p-2 form-control font-sm bg-white font-weight-600 text-black h-auto" readonly value="{{$shippingCarrier->name ?? ''}}"  >
                                        </div>
                                    </div>
                                </div>

                                @foreach($configuration as $key => $aConfiguration)
                                    <!-- @if(!$aConfiguration['setup_field']) @continue; @endif -->

                                    @if($aConfiguration['connection_oriented'] && $aConfiguration['editable'] && $aConfiguration['type'] != 'DROPDOWN' && $aConfiguration['type'] != 'CHECKBOX')
                                        <input type="hidden" name="configurations[{{$key}}][field]" value="{{$aConfiguration['field']}}">

                                        @include($aConfiguration['type'] == 'TEXTAREA' ? 'shared.forms.textarea' : 'shared.forms.input', [
                                            'name' => "configurations[$key][value]",
                                            'label' => __($aConfiguration['title']),
                                            'value' => $aConfiguration['value'] ?? ''
                                        ])
                                    @endif
                                    @if($aConfiguration['connection_oriented'] && $aConfiguration['editable'] &&$aConfiguration['type'] == 'DROPDOWN')
                                        <input type="hidden" name="configurations[{{$key}}][field]" value="{{$aConfiguration['field']}}">

                                    @include('shared.forms.select', [
                                        'label' => $aConfiguration['title'],
                                        'containerClass' => 'w-100 text-capitalize',
                                        'class' => 'text-capitalize w-100',
                                        'name' => "configurations[$key][value]",
                                        'value' => $aConfiguration['value'] ?? $aConfiguration['default_value'] ?? '',
                                        'options' => $aConfiguration['options'] ?? [],
                                        'placeholder' => 'Select ' . $aConfiguration['title']
                                    ])
                                    @endif
                                    @if($aConfiguration['connection_oriented'] && $aConfiguration['editable'] && $aConfiguration['type'] == 'CHECKBOX')
                                        <input type="hidden" name="configurations[{{$key}}][field]" value="{{$aConfiguration['field']}}">
                                        
                                        @include('shared.forms.checkbox', [
                                            'name' => "configurations[$key][value]",
                                            'label' => $aConfiguration['title'],
                                            'checked' => $aConfiguration['value'] ?? ''
                                        ])
                                    @endif
                                @endforeach
                                <div class="text-center">
                                    <button type="submit" id="shippingCarrierSubmit" class="btn bg-logoOrange mx-auto px-5 font-weight-700 text-sm mt-5 change-tab text-white">{{ __('Save') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script>
        new ShippingCarrier()
    </script>
@endpush
