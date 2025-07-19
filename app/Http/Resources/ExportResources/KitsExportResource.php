<?php

namespace App\Http\Resources\ExportResources;

use Illuminate\Http\Request;

class KitsExportResource extends ExportResource
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
            'parent_sku' => $this['parent_sku'],
            'child_sku' => $this['child_sku'],
            'quantity' => $this['quantity'],
            'update_orders' => $this['update_orders'],
        ];
    }

    public static function columns(): array
    {
        return [
            'parent_sku',
            'child_sku',
            'quantity',
            'update_orders'
        ];
    }
}
