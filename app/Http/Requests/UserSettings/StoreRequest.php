<?php

namespace App\Http\Requests\UserSettings;

use App\Http\Requests\FormRequest;
use App\Models\UserSetting;

class StoreRequest extends FormRequest
{
    public static function validationRules()
    {
        $rules = [];

        $rules['key'] = ['sometimes'];
        $rules['value'] = ['sometimes'];

        foreach (UserSetting::USER_SETTING_KEYS as $key) {
            $rules[$key] = ['sometimes'];
        }

        return $rules;
    }
}
