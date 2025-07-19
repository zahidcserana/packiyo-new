<?php

namespace App\Http\Resources\ExportResources;

use Illuminate\Http\Request;

class LocationTypeExportResource extends ExportResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'customer'                  => $this->customer->contactInformation->name,
            'name'                      => $this->name,
            'disabled_on_picking_app'   => $this->disabled_on_picking_app ? 'YES' : 'NO',
            'pickable'                  => $this->pickable ? 'YES' : 'NO',
            'sellable'                  => $this->sellable ? 'YES' : 'NO',
        ];
    }

    public static function columns(): array
    {
        return [
            'customer',
            'name',
            'disabled_on_picking_app',
            'pickable',
            'sellable',
        ];
    }
}
