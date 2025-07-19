<?php

namespace App\Http\Resources\ExportResources;

use Illuminate\Http\Request;

class LotInventoryReportExportResource extends ExportResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'lot_id' => $this->lot->name,
            'product_name' => $this->lot->product->name,
            'sku' => $this->lot->product->sku,
            'location' => $this->location->name,
            'expiration_date' => user_date_time($this->lot->expiration_date, true),
            'on_hand' => $this->quantity_remaining,
            'item_price' => $this->lot->item_price,
            'lot_value' => $this->lot->item_price * $this->quantity_remaining,
            'warehouse' => $this->location->warehouse->contactInformation->name,
        ];
    }

    public static function columns(): array
    {
        return [
            'Lot ID',
            'Product name',
            'SKU',
            'Location',
            'Expiration date',
            'On hand',
            'Item price',
            'Lot value',
            'Warehouse'
        ];
    }
}
