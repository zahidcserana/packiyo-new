<tr class="order-item-fields {{isset($item['from']) ? '' : 'd-none'}}">
    <td>
        <div class="row">
            <div class="col-12 col-sm-6 d-flex align-items-center mb-2 mb-sm-0">
                <label class="m-0 mr-2">
                    {{__('From:')}}
                </label>
                <input
                    class="form-control form-control-sm item_from"
                    name="settings[pick_range_fees][{{$key ?? 0}}][from]"
                    value="{{ $item['from'] ?? 0}}"
                    readonly
                >
            </div>
            <div class="col-12 col-sm-6 d-flex align-items-center">
                <label class="m-0 mr-2">
                    {{__('To:')}}
                </label>
                <input
                    class="form-control form-control-sm item_to"
                    name="settings[pick_range_fees][{{$key ?? 0}}][to]"
                    value="{{ $item['to'] ?? 1}}"
                >
            </div>
        </div>
    </td>
    <td>
        <div class="row">
            <div class="col">
                @include('shared.forms.input', [
                    'name' => 'settings[pick_range_fees][' . ($key ?? 0) . '][fee]',
                    'type' => 'number',
                    'label' => '',
                    'labelClass' => 'd-none',
                    'containerClass' => 'm-0',
                    'value' => $item['fee'] ?? 0,
                    'class' => 'item_rate',
                    'step' => '0.01'
                ])
            </div>
            <div class="col-auto d-flex align-items-center pl-0 remove-item-col">
                <a class="btn btn-primary btn-sm text-white remove-item">
                    {{ __('×') }}
                </a>
            </div>
        </div>
    </td>
</tr>
