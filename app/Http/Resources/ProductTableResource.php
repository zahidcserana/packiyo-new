<?php

namespace App\Http\Resources;

use App\Features\MultiWarehouse;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Pennant\Feature;
use App\Models\Product;
use Illuminate\Support\Arr;

class ProductTableResource extends JsonResource
{
    public function toArray($request): array
    {
        unset($resource);

        $resource['id'] = $this->id;
        $resource['sku'] = $this->sku;
        $resource['name'] = $this->name;
        $resource['price'] = $this->price;
        $resource['cost'] = $this->cost;
        $resource['notes'] = $this->notes;
        $resource['quantity'] = $this->quantity;
        $resource['quantity_on_hand'] = $this->quantity_on_hand;
        $resource['quantity_inbound'] = max(0, $this->quantity_inbound);
        $resource['quantity_reserved'] = $this->quantity_reserved;
        $resource['quantity_pending'] = $this->quantity_pending ?? null;
        $resource['quantity_available'] = $this->quantity_available;
        $resource['quantity_allocated'] = $this->quantity_allocated;
        $resource['quantity_backordered'] = $this->quantity_backordered;
        $resource['quantity_sell_ahead'] = $this->quantity_sell_ahead;
        $resource['warehouse'] = $this->customer->contactInformation['name'];

        $resource['height'] = $this->height;
        $resource['width'] = $this->width;
        $resource['length'] = $this->length;
        $resource['weight'] = $this->weight;
        $resource['barcode'] = join('<br />', $this->barcodes());
        $resource['hs_code'] = $this->hs_code;
        $resource['value'] = $this->value;
        $resource['date'] = user_date_time($this->created_at);
        $resource['is_kit'] = $this->isKit() ? __('Yes') : __('No');
        $resource['inventory_sync'] = $this->inventory_sync ? __('Yes') : __('No');
        $resource['image'] = $this->productImages->first()->source ?? asset('img/no-image.png');
        $resource['tags'] = $this->tags->pluck('name')->join(', ');
        $resource['suppliers'] = $this->suppliersLink();
        $resource['customer'] = [
            'name' => $this->customer->contactInformation->name,
            'url' => route('customer.edit', ['customer' => $this->customer]),
        ];
        $resource['link_edit'] = route('product.edit', ['product' => $this]);
        $resource['link_delete'] = [
            'token' => csrf_token(), 'url' => route('product.destroy', ['product' => $this]),
        ];
        $resource['is_deleted'] = (int)isset($this->deleted_at);

        $resource['print_barcode_button'] = view('components.print_modal_button', [
            'submitAction' => route('product.barcodes', $this),
            'pdfUrl' => route('product.barcode', $this),
            'customerPrintersUrl' => route('product.getCustomerPrinters', $this),
        ])->render();

        $resource['hazmat'] = $this->hazmat ? __(Arr::get(Product::HAZMAT_OPTIONS, $this->hazmat, '')) : '';
        $resource['client'] = $this->customer->contactInformation->name;

        if (Feature::for('instance')->active(MultiWarehouse::class)) {
            $resource['product_warehouses'] = $this->productWarehouses->map(static function ($productWarehouse) {
                return ['warehouse_name' => $productWarehouse->warehouse->contactInformation->name] +
                    $productWarehouse->only([
                        'quantity_on_hand',
                        'quantity_reserved',
                        'quantity_pickable',
                        'quantity_allocated',
                        'quantity_allocated_pickable',
                        'quantity_available',
                        'quantity_to_replenish',
                        'quantity_backordered',
                        'quantity_sell_ahead',
                    ]);
            })->toArray();
        }

        return $resource;
    }
}
