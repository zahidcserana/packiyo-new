<?php

namespace App\Http\Resources\ExportResources;

use Illuminate\Http\Request;

class DuplicateBarcodeReportExportResource extends ExportResource
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
            'customer' => $this->customer->contactInformation->name,
            'sku' => $this->sku,
            'name' => $this->name,
            'barcode' => $this->barcode
        ];
    }

    public static function columns(): array
    {
        return [
            'customer',
            'sku',
            'name',
            'barcode'
        ];
    }
}
