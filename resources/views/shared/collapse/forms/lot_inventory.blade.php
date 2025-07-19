@include('shared.collapse.forms._customer')
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Expire After') }}</label>
    <input class="form-control datetimepicker" type="text" name="start_date">
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Expire Before') }}</label>
    <input class="form-control datetimepicker" type="text" name="end_date">
</div>
<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Exclude empty On Hand') }}</label>
    <select name="exclude_empty" class="form-control">
        <option value="1" selected>{{ __('Yes') }}</option>
        <option value="0">{{ __('No') }}</option>
    </select>
</div>
