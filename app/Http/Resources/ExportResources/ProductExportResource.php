<?php

namespace App\Http\Resources\ExportResources;

use Illuminate\Http\Request;

class ProductExportResource extends ExportResource
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
            'sku' => $this->sku,
            'name' => $this->name,
            'barcode' => $this->barcode,
            'price' => $this->price,
            'cost' => $this->cost,
            'value' => $this->value,
            'hs_code' => $this->hs_code,
            'weight' => $this->weight,
            'height' => $this->height,
            'length' => $this->length,
            'width' => $this->width,
            'customs_price' => $this->customs_price,
            'customs_description' => $this->customs_description,
            'quantity_on_hand' => $this->quantity_on_hand,
            'quantity_available' => $this->quantity_available,
            'quantity_allocated' => $this->quantity_allocated,
            'quantity_backordered' => $this->quantity_backordered,
            'quantity_reserved' => $this->quantity_reserved,
            'quantity_inbound' => max(0, $this->quantity_inbound),
            'quantity_sell_ahead' => $this->quantity_sell_ahead,
            'country_of_origin' => $this->country->iso_3166_2 ?? '',
            'notes' => $this->notes,
            'image' => $this->productImages->first()->source ?? '',
            'vendor' => $this->suppliers->pluck('contactInformation.name')->join(';'),
            'customer' => $this->customer->contactInformation->name
        ];
    }

    public static function columns(): array
    {
        return [
            'sku',
            'name',
            'barcode',
            'price',
            'cost',
            'value',
            'hs_code',
            'weight',
            'height',
            'length',
            'width',
            'customs_price',
            'customs_description',
            'quantity_on_hand',
            'quantity_available',
            'quantity_allocated',
            'quantity_backordered',
            'quantity_reserved',
            'quantity_inbound',
            'quantity_sell_ahead',
            'country_of_origin',
            'notes',
            'image',
            'vendor',
            'customer'
        ];
    }
}
