<?php

namespace App\Http\Resources;

use App\Features\MultiWarehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Pennant\Feature;

class ReplenishmentReportTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        unset($resource);

        $resource['product_name'] = $this->name;
        $resource['product_url'] = route('product.edit', ['product' => $this]);
        $resource['sku'] = $this->sku;
        $resource['quantity_on_hand'] = $this->quantity_on_hand;
        $resource['quantity_allocated'] = $this->quantity_allocated;
        $resource['quantity_pickable'] = max(0, $this->quantity_pickable);
        $resource['qty'] = $this->quantity_to_replenish;
        $resource['locations'] = $this->replenishmentLocations()->take(3)->pluck('name')->join(', ');

        if (Feature::for('instance')->active(MultiWarehouse::class)) {
            $resource['product_warehouses'] = $this->productWarehouses->map(static function ($productWarehouse) {
                return ['warehouse_name' => $productWarehouse->warehouse->contactInformation->name] +
                    $productWarehouse->only([
                        'quantity_on_hand',
                        'quantity_pickable',
                        'quantity_allocated',
                        'quantity_to_replenish'
                    ]);
            })->toArray();
        }

        return $resource;
    }
}
