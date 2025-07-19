<form method="post" autocomplete="off" class="modal-content" id="create-user-form" action="{{ route('user.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="modal-header border-bottom">
        <h6 class="modal-title">{{ __('Add User') }}</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-12 col-sm-6">
                @include('shared.forms.input', [
                   'name' => 'email',
                   'label' => __('Email'),
                   'type' => 'email'
                ])
            </div>
            <div class="col-12 col-sm-6">
                @include('shared.forms.input', [
                   'name' => 'contact_information[name]',
                   'label' => __('Name'),
                   'type' => 'text'
                ])
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-sm-6">
                @include('shared.forms.input', [
                   'name' => 'password',
                   'label' => __('Password'),
                   'type' => 'password',
                   'autocomplete' => 'new-password',
                ])
            </div>
            <div class="col-12 col-sm-6">
                @include('shared.forms.input', [
                   'name' => 'password_confirmation',
                   'label' => __('Confirm Password'),
                   'type' => 'password',
                   'autocomplete' => 'new-password',
                ])
            </div>
        </div>
        @if(isset($sessionCustomer))
            @include('shared.forms.input', [
                 'name' => 'customer_id',
                 'value' => $sessionCustomer->id,
                 'type' => 'hidden'
            ])
        @else
            @if(app()->user->getCustomers()->count() > 1)
                <div class="row">
                    <div class="col-12">
                        @include('shared.forms.new.ajaxSelect', [
                            'url' => route('user.getCustomers'),
                            'name' => 'customer_id',
                            'placeholder' => __('Select customer'),
                            'label' => __('Customer'),
                            'className' => 'ajax-user-input customer_id customer-id-select',
                            'default' => [
                                'id' => old('customer_id'),
                                'text' => ''
                            ],
                            'fixRouteAfter' => '.ajax-user-input.customer_id'
                        ])
                    </div>
                </div>
            @else
                @include('shared.forms.input', [
                     'name' => 'customer_id',
                     'value' => app()->user->getCustomers()->first()->id,
                     'type' => 'hidden'
                ])
            @endif
        @endif
        @if(isset($sessionCustomer))
            <div class="row">
                <div class="col-12 col-sm-6 mt-2">
                    <label class="form-control-label text-neutral-text-gray font-weight-600 font-xs">{{ __('Warehouse') }}</label>
                    <select name="warehouse_id" class="form-control" data-toggle="select" data-placeholder="">
                        <option value="">{{ __('All') }}</option>
                        @foreach($sessionCustomer->warehouses as $warehouse)
                            <option value="{{$warehouse->id}}">{{$warehouse->contactInformation->name}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif
        @include('shared.forms.input', [
             'name' => 'customer_user_role_id',
             'value' => \App\Models\CustomerUserRole::ROLE_DEFAULT,
             'type' => 'hidden'
        ])
        <hr class="m-0 mt-2 pt-4"/>
        <div class="row">
            <div class="col-12">
                <label class="toggle m-0">
                    <span class="form-control-label text-truncate">{{__('Admin')}}</span>
                    <input type="checkbox" name="is_admin">
                    <span class="toggle-slider"></span>
                </label>
                @include('shared.forms.input', [
                    'name' => 'user_role_id',
                    'value' => 2,
                    'type' => 'hidden'
                ])
            </div>
        </div>
    </div>
    <div class="modal-footer border-top d-flex justify-content-center">
        <button data-dismiss="modal" class="btn mx-2">{{ __('Cancel') }}</button>
        <button type="submit" class="btn bg-logoOrange text-white mx-2">{{ __('Save') }}</button>
    </div>
</form>

<script type="text/javascript">
    if ($('.customer-id-select').length) {
        $('.customer-id-select').select2();
    }
</script>
