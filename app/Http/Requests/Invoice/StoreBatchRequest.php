<?php

namespace App\Http\Requests\Invoice;

use App\Http\Requests\FormRequest;

class StoreBatchRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'start_date' => 'required',
            'end_date' => 'required',
            'store_customers_selected' => 'required',
        ];
    }
}
