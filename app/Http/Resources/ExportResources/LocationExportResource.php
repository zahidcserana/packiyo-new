<?php

namespace App\Http\Resources\ExportResources;

use App\Http\Resources\Request;

class LocationExportResource extends ExportResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $resource = [];

        $resource = [
            'warehouse' => $this->warehouse->contactInformation->name,
            'name'      => $this->name,
            'type'      => $this->locationType->name ?? '',
            'barcode'   => $this->barcode,
            'pickable'  => $this->pickable ? 'YES' : 'NO',
            'sellable'  => $this->sellable ? 'YES' : 'NO',
            'bulk_ship_pickable'  => $this->bulk_ship_pickable ? 'YES' : 'NO',
            'disabled_on_picking_app'  => $this->disabled_on_picking_app ? 'YES' : 'NO',
            'is_receiving' => $this->is_receiving ? 'YES' : 'NO'
        ];

        return $resource;
    }

    public static function columns(): array
    {
        return [
            'warehouse',
            'name',
            'type',
            'barcode',
            'pickable',
            'sellable',
            'bulk_ship_pickable',
            'disabled_on_picking_app',
            'is_receiving',
        ];
    }
}
