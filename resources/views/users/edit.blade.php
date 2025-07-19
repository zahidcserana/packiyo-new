<form method="post" autocomplete="off" class="modal-content" id="edit-user-form" action="{{ route('user.update', $user) }}" data-type="PUT" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="modal-header border-bottom">
        <h6 class="modal-title">{{ __('Edit User') }} <span class="opacity-4 font-weight-400 font-xs">/ {{ $user->email ?? '' }}</span></h6>
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
                   'type' => 'email',
                   'value' => $user->email ?? ''
                ])
            </div>
            <div class="col-12 col-sm-6">
                @include('shared.forms.input', [
                   'name' => 'contact_information[name]',
                   'label' => __('Name'),
                   'type' => 'text',
                   'value' => $user->contactInformation->name ?? ''
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
            @php
                $userWarehouse = App\Models\CustomerUser::whereUserId($user->id)->whereCustomerId($sessionCustomer->id)->first();
            @endphp
            <div class="row">
                <div class="col-12 col-sm-6 mt-2">
                    <label class="form-control-label text-neutral-text-gray font-weight-600 font-xs">{{ __('Warehouse') }}</label>
                    <select name="warehouse_id" class="form-control" data-toggle="select" data-placeholder="">
                        <option value="">{{ __('All') }}</option>
                        @foreach($sessionCustomer->warehouses as $warehouse)
                            @if($userWarehouse && $userWarehouse->warehouse_id === $warehouse->id)
                                <option value="{{$warehouse->id}}" selected>{{$warehouse->contactInformation->name}}</option>
                            @else
                                <option value="{{$warehouse->id}}">{{$warehouse->contactInformation->name}}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
        @endif
        @if(auth()->user()->isAdmin())
            <hr class="m-0 mt-1 pt-4"/>
            <div class="row">
                <div class="col-6">
                    <label class="toggle m-0">
                        <span class="form-control-label text-truncate">{{ __('Enabled') }}</span>
                        <input type="checkbox" name="disabled_at" {{ $user->disabled_at ? '' : 'checked' }}>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="col-6">
                    <label class="toggle m-0">
                        <span class="form-control-label text-truncate">{{ __('Admin') }}</span>
                        <input type="checkbox" name="is_admin" {{ $user->user_role_id === \App\Models\UserRole::ROLE_ADMINISTRATOR ? 'checked' : ''}}>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        @endif
        @include('shared.forms.input', [
            'name' => 'user_role_id',
            'value' => $user->user_role_id ?? \App\Models\UserRole::ROLE_DEFAULT,
            'type' => 'hidden'
        ])
    </div>
    <div class="modal-footer border-top d-flex justify-content-center">
        <button data-dismiss="modal" class="btn mx-2">{{ __('Cancel') }}</button>
        <button type="submit" class="btn bg-logoOrange text-white mx-2">{{ __('Save') }}</button>
    </div>
</form>
