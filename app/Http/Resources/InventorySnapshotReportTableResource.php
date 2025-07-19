<?php

namespace App\Http\Resources;

use App\Exceptions\InventoryException;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventorySnapshotReportTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $productName = null;
        $productImage = null;

        // TODO: Handle with schema versioning.
        if (!array_key_exists('name', $this->product)) {
            $product = Product::find($this->product['id']);

            if (is_null($product)) {
                throw new InventoryException(
                    'When generating the Inventory Snapshot report, the cached product ID '
                    . $this->product['id']
                    . ' did not have a corresponding product in the rel db.'
                );
            }

            $productName = $product->name;
            $productImage = $product->productImages->first()->source ?? asset('img/no-image.png');
        }

        return [
            // TODO should we query from the database?
            'image' => $this->product['image'] ?? $productImage,
            'customer' => $this->product['customer']['name'],
            'sku' => $this->product['sku'],
            'name' => $this->product['name'] ?? $productName,
            'warehouse' => $this->warehouse['name'],
            'quantity_on_hand' => $this->inventory['last'],
            'link_edit' => route('product.edit', ['product' => $this->product['id']]),
            'location_name' => $this->location['name'],
        ];
    }
}
