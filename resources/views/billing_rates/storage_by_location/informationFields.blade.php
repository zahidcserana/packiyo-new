
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
        'label' => __('Invoice Code'),
        'value' => $billingRate['code'] ?? '',
    ])

    @include('shared.forms.checkbox', [
        'name' => 'settings[no_location]',
        'label' => __('No Location Type'),
        'value' => 1,
        'checked' => $settings['no_location'] ?? false
    ])
    <div class="table-responsive p-0">
        <input class="location_type_selectables" type="hidden" name="settings[location_types]" value="{{old('settings.location_types', $settings['location_types'] ?? '[]')}}">
        <label class="form-control-label" for="selectables-table">{{ __('Assigned Location Types') }}</label>
        <table class="table items-table align-items-center table-hover col-12 p-0 selectables-table" style="width: 100% !important;" data-selectables="location_type_selectables" data-url="{{ route('locationType.dataTable') }}">
            <thead></thead>
            <tbody></tbody>
        </table>
    </div>
    @include('shared.forms.input', [
        'name' => 'settings[fee]',
        'label' => __('Fee - Amount charged'),
        'value' => $settings['fee'] ?? 0,
        'type' => 'number',
        'step' => '0.001'
    ])
    <div class="form-group show-or-not{{$isReadonlyUser ?? '' ? ' disable-for-user-group' : '' }}">
        <label class="form-control-label">{{ __('Cycle - The amount of time allotted for a storage charge to be applied') }}</label>
        <select name="{{'settings[period]'}}" class="form-control" data-toggle="select" data-placeholder="">
            @foreach(\App\Models\BillingRate::PERIODS as $period)
                <option value="{{$period}}"

                @if(isset($settings) || old('settings')):
                    {{$period === old('settings.period',$settings['period'] ?? false) ? 'selected' : ''}}
                @endif
                >
                    {{$period}}
                </option>
            @endforeach
        </select>
    </div>
</div>
@push('js')
    <script>
        new BillingRateCheckForDuplicateRates();
    </script>
@endpush
