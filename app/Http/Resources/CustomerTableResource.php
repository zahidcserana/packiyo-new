<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\CustomerSetting;

class CustomerTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        unset($resource);

        $storeName = customer_settings($this->id, CustomerSetting::CUSTOMER_SETTING_EEL_PFC);

        $resource['name'] = $this->contactInformation->name;
        $resource['company_name'] = $this->contactInformation->company_name;
        $resource['store_name'] = $storeName;
        $resource['address'] = $this->contactInformation->address;
        $resource['address2'] = $this->contactInformation->address2;
        $resource['zip'] = $this->contactInformation->zip;
        $resource['city'] = $this->contactInformation->city;
        $resource['email'] = $this->contactInformation->email;
        $resource['phone'] = $this->contactInformation->phone;
        $resource['link_edit'] =  route('customer.edit', ['customer' => $this]);
        $resource['link_store'] =  'https://' . $storeName . '.' . env('APP_DOMAIN');
        $resource['link_delete'] = [
            'token' => csrf_token(),
            'url' => route('customer.destroy', ['id' => $this->id, 'customer' => $this])
        ];

        return $resource;
    }
}
