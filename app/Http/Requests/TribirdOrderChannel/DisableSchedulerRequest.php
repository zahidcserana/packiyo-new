<?php

namespace App\Http\Requests\TribirdOrderChannel;

use App\Http\Requests\FormRequest;

class DisableSchedulerRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'type' => [
                'required'
            ]
        ];
    }
}
