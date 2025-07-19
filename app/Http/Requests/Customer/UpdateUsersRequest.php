<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\FormRequest;

class UpdateUsersRequest extends FormRequest
{
    public static function validationRules($includeContactInformationRules = true): array
    {
        return [
            'new_user_id' => [
                'sometimes',
                'exists:users,id'
            ],
            'new_user_role_id' => [
                'sometimes',
                'exists:customer_user_roles,id'
            ],
            'new_user_warehouse_id' => [
                'nullable',
                'exists:warehouses,id'
            ],
            '*.user_id' => [
                'sometimes',
                'exists:users,id'
            ],
            '*.role_id' => [
                'sometimes',
                'exists:customer_user_roles,id'
            ],
            '*.warehouse_id' => [
                'sometimes',
                'exists:warehouses,id'
            ]
        ];
    }
}
