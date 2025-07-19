<?php

namespace App\Http\Requests\Invoice;

use App\Http\Requests\FormRequest;

class RecalculateBatchRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'dates_between' => 'required',
            'recalculate_customers_selected' => 'required'
        ];
    }
}
