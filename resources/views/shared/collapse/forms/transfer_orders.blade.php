<div class="form-group col-12 col-md-3">
    <label for="" class="font-xs">{{ __('Order Status') }}</label>
    <select name="status" class="form-control" id="statuses">
        <option value="0">{{ __('All') }}</option>
        <option value="created">{{ __('Created') }}</option>
        <option value="shipped">{{ __('Shipped') }}</option>
        <option value="received">{{ __('Received') }}</option>
        <option value="closed">{{ __('Closed') }}</option>
    </select>
</div>
