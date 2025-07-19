<?php

namespace App\Http\Dto\Filters\Reports;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class ShipmentReportFilterDto implements Arrayable
{
    public Collection $shippingCarriers;
    public Collection $shippingMethods;
    public Collection $warehouses;

    /**
     * @param Collection $shippingCarriers
     * @param Collection $shippingMethods
     * @param Collection $warehouses
     */
    public function __construct(Collection $shippingCarriers, Collection $shippingMethods, Collection $warehouses)
    {
        $this->shippingCarriers = $shippingCarriers;
        $this->shippingMethods = $shippingMethods;
        $this->warehouses = $warehouses;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'shipping_carriers' => $this->shippingCarriers,
            'shipping_methods' => $this->shippingMethods,
            'warehouses' => $this->warehouses,
        ];
    }
}
