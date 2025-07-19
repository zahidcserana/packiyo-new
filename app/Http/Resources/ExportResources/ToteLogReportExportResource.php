<?php

namespace App\Http\Resources\ExportResources;

use Illuminate\Http\Request;

class ToteLogReportExportResource extends ExportResource
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
            'tote_name' => $this->tote->name,
            'order_number' => $this->orderItem->order->number,
            'sku' => $this->orderItem->sku,
            'product_name' => $this->orderItem->name,
            'quantity_added' => $this->quantity,
            'quantity_removed' => $this->quantity_removed,
            'date_added' => user_date_time($this->created_at, true),
            'picked_by' => $this->user->contactInformation->name ?? ''
        ];
    }

    public static function columns(): array
    {
        return [
            'Tote name',
            'Order number',
            'SKU',
            'Product name',
            'Quantity added',
            'Quantity removed',
            'Date added',
            'Picked by'
        ];
    }
}
