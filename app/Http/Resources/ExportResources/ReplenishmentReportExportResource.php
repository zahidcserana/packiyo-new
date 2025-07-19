<?php

namespace App\Http\Resources\ExportResources;

use Illuminate\Http\Request;

class ReplenishmentReportExportResource extends ExportResource
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
            'name' => $this->name,
            'sku' => $this->sku,
            'on_hand' => $this->quantity_on_hand,
            'allocated' => $this->quantity_allocated,
            'pickable_amount' => max(0, $this->quantity_pickable),
            'qty' => $this->quantity_to_replenish,
            'affected_orders' => $this->orderItem->groupBy('order_id')->count(),
            'non_pickable_location' => $this->locations->where('pickable', 0)->first() ? $this->locations->where('pickable', 0)->first()->name : '-',
            'locations' => $this->replenishmentLocations()->take(3)->pluck('name')->join(', ')
        ];
    }

    public static function columns(): array
    {
        return [
            'name',
            'sku',
            'on_hand',
            'allocated',
            'pickable_amount',
            'qty',
            'affected_orders',
            'non_pickable_location',
            'locations',
        ];
    }
}
