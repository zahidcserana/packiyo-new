<?php

namespace App\Http\Requests\SteadfastCredential;

use App\Http\Requests\FormRequest;

class UpdateBatchRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return UpdateRequest::prefixedValidationRules('*.');
    }
}
