<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Customer') }}</label>
    <select name="customers.id" class="form-control">
        <option value="0">{{ __('All') }}</option>
        @foreach($data['batchCustomers'] as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Type of charge') }}</label>
    <select name="billing_rates.type" class="form-control">
        <option value="0">{{ __('All') }}</option>
        @foreach(\App\Models\BillingRate::BILLING_RATE_TYPES as $type => $info)
            <option value="{{ $type }}">{{ $info['title'] }}</option>
        @endforeach
    </select>
</div>
