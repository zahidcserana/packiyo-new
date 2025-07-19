<?php

namespace App\Http\Requests\Invoice;

use App\Http\Requests\FormRequest;

class BatchStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'start_date' => 'required',
            'end_date' => 'required',
            'customer_ids' => [
                'required',
                'array',
            ]
        ];
    }

}
