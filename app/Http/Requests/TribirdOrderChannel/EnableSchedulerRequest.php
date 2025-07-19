<?php

namespace App\Http\Requests\TribirdOrderChannel;

use App\Http\Requests\FormRequest;

class EnableSchedulerRequest extends FormRequest
{
    public static function validationRules(): array
    {
        return [
            'cron_expression' => [
                'required'
            ],
            'type' => [
                'required'
            ]
        ];
    }
}
