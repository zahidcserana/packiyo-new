<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Start Date') }}</label>
    <input class="form-control datetimepicker" type="text" value="{{ user_date_time(now()->subWeeks(2)->startOfDay(), true) }}" name="start_date">
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('End Date') }}</label>
    <input class="form-control datetimepicker" type="text" value="" name="end_date">
</div>
<div class="form-group col-12 col-md-3">
    <label for="sku" class="font-xs">SKU</label>
    <input class="form-control" type="text" name="sku" id="sku">
</div>
<div class="form-group col-12 col-md-3">
    <label for="sku" class="font-xs">Tote</label>
    <input class="form-control" type="text" name="tote" id="tote">
</div>
<div class="form-group col-12 col-md-3">
    <label for="order" class="font-xs">Order Number</label>
    <input class="form-control" type="text" name="order" id="order">
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Picked by') }}</label>
    <select name="user_id" class="form-control">
        <option value="0">{{ __('All') }}</option>
        @foreach($data['users'] as $user)
            <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
        @endforeach
    </select>
</div>
