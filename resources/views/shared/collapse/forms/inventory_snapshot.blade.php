@include('shared.collapse.forms._customer')
<div class="form-group col-12 col-md-3">
    <label for="warehouse_id" class="font-xs">{{ __('Warehouse') }}</label>
    <select name="warehouse_id" class="form-control" id="warehouse_id">
        <option value="0">{{ __('All') }}</option>
        @foreach($data['warehouses'] as $id => $warehouse)
            <option value="{{ $id }}">{{ $warehouse }}</option>
        @endforeach
    </select>
</div>
@include('shared.forms.input', [
    'name' => 'date',
    'label' => 'Date',
    'value' => user_date_time(now()->subDay()),
    'class' => 'dt-daterangepicker text-right'
])
