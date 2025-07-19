<?php

namespace App\Http\Resources\ExportResources;

use Illuminate\Http\Request;

class SupplierExportResource extends ExportResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'currency' => $this->currency,
            'internal_note' => $this->internal_note,
            'default_purchase_order_note' => $this->default_purchase_order_note,
            'vendor_contact_information_name' => $this->contactInformation->name,
            'vendor_contact_information_company_name' => $this->contactInformation->company_name,
            'vendor_contact_information_company_number' => $this->contactInformation->company_number,
            'vendor_contact_information_address' => $this->contactInformation->address,
            'vendor_contact_information_address2' => $this->contactInformation->address2,
            'vendor_contact_information_zip' => $this->contactInformation->zip,
            'vendor_contact_information_city' => $this->contactInformation->city,
            'vendor_contact_information_state' => $this->contactInformation->state,
            'vendor_contact_information_country' => $this->contactInformation->country->iso_3166_2 ?? '',
            'vendor_contact_information_email' => $this->contactInformation->email,
            'vendor_contact_information_phone' => $this->contactInformation->phone,
        ];
    }

    public static function columns(): array
    {
        return [
            'currency',
            'internal_note',
            'default_purchase_order_note',
            'vendor_contact_information_name',
            'vendor_contact_information_company_name',
            'vendor_contact_information_company_number',
            'vendor_contact_information_address',
            'vendor_contact_information_address2',
            'vendor_contact_information_zip',
            'vendor_contact_information_city',
            'vendor_contact_information_state',
            'vendor_contact_information_country',
            'vendor_contact_information_email',
            'vendor_contact_information_phone',
        ];
    }
}
