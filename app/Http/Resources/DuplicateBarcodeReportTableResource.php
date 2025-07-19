<?php

namespace App\Http\Resources;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DuplicateBarcodeReportTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        unset($resource);

        $customer = Customer::find($this->customer_id);

        $resource['customer'] = [
            'name' => $this->customer_name,
            'url' => route('customer.edit', ['customer' => $customer]),
            'is_3pl_child' => $customer->is3plChild()
        ];

        $productSkus = explode(',', $this->product_skus);

        $resource['product'] = [];

        foreach ($productSkus as $productSku) {
            $product = Product::whereSku($productSku)
                ->where('customer_id', $customer->id)
                ->first();

            if (!is_null($product)) {
                $resource['products'][] = [
                    'sku' => $productSku,
                    'name' => $product->name,
                    'url' => route('product.edit', ['product' => $product])
                ];
            }
        }

        $resource['barcode'] = $this->barcode;

        return $resource;
    }
}
