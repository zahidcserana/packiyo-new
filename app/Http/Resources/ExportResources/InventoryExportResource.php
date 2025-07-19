<?php

namespace App\Http\Resources\ExportResources;

use Illuminate\Http\Request;

class InventoryExportResource extends ExportResource
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
            'sku' => $this->product->sku,
            'location' => $this->location->name,
            'lot_name' => $this->lot_name,
            'quantity' => $this->lot_item_quantity_remaining ?? $this->quantity_on_hand,
            'action' => 'replace'
        ];
    }

    public static function columns(): array
    {
        return [
            'sku',
            'location',
            'lot_name',
            'quantity',
            'action (replace, increase, decrease)'
        ];
    }
}
