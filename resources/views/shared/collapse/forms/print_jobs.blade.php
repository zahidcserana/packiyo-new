<div class="form-group col-12 col-md-3">
    <label for=""
           class="font-xs"
           data-id="country">{{ __('Created Date') }}</label>
    <select
        class="form-control"
        type="text"
        name="created_date">
        <option value="0">{{ __('All') }}</option>
        <option value="{{ \Carbon\Carbon::now()->subWeek()->format('Y-m-d') }}">{{ __('In The Last Week') }}</option>
        <option value="{{ \Carbon\Carbon::now()->subMonth()->format('Y-m-d') }}">{{ __('In The Last Month') }}</option>
        <option value="{{ \Carbon\Carbon::now()->subYear()->format('Y-m-d') }}">{{ __('In The Last Year') }}</option>
    </select>
</div>
