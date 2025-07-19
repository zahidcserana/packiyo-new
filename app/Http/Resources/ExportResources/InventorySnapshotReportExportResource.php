<?php

namespace App\Http\Resources\ExportResources;

use Illuminate\Http\Request;

class InventorySnapshotReportExportResource extends ExportResource
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
            'customer' => $this->product['customer']['name'],
            'sku' => $this->product['sku'],
            'name' => $this->product['name'],
            'warehouse' => $this->warehouse['name'],
            'quantity_on_hand' => $this->inventory['last'],
        ];
    }

    public static function columns(): array
    {
        return [
            'customer',
            'sku',
            'name',
            'warehouse',
            'quantity_on_hand',
        ];
    }
}
