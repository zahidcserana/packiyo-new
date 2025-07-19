<?php

namespace App\Http\Resources\ExportResources;

use Illuminate\Http\Request;

class ShippingBoxExportResource extends ExportResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $resource = [
            'customer' => $this->customer->contactInformation->name,
            'name' => $this->name,
            'height' => $this->height,
            'length' => $this->length,
            'width' => $this->width,
            'weight' => $this->weight,
            'cost'=> $this->cost
        ];

        return $resource;
    }

    public static function columns(): array
    {
        return [
            'customer',
            'name',
            'height',
            'length',
            'width',
            'weight',
            'cost'
        ];
    }
}
