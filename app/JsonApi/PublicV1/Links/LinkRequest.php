<?php

namespace App\JsonApi\PublicV1\Links;

use App\Models\UserSetting;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class LinkRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
            ],
            'url' => [
                'required',
                'url',
            ],
            'is_printable' => [
                'required'
            ],
            'printer_type' => [
                'nullable',
                'string',
                'in:' . implode(',',UserSetting::USER_SETTING_PRINTER_KEYS),
            ],
            'shipment_id' => [
                'required',
                'exists:shipments,id,deleted_at,NULL'
            ],
        ];
    }

    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        $input = $this->input();

        $shipmentId = $this->input('data.relationships.shipment.data.id');
        $input['data']['attributes']['shipment_id'] = $shipmentId;

        if (isset($input['data']['attributes']['object_type'])) {
            $input['data']['attributes']['object_type'] = 'App\\Models\\' . $input['data']['attributes']['object_type'];
        }

        $this->replace($input);
    }
}
