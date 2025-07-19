<?php

namespace App\Http\Resources\ExportResources;

use Illuminate\Http\Request;

class ReturnedProductReportExportResource extends ExportResource
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
            'sku' => $this->product_sku,
            'orders_returned' => $this->orders_returned,
            'quantity_requested' => $this->quantity_requested,
            'quantity_returned' => $this->quantity_returned       
        ];
    }

    public static function columns(): array
    {
        return [
            'SKU',
            'Orders returned',
            'Units requested',
            'Units returned'
        ];
    }
}
