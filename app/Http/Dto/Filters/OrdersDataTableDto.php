<?php

namespace App\Http\Dto\Filters;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class OrdersDataTableDto implements Arrayable
{
    public Collection $customers;
    public Collection $orderStatuses;
    public Collection $shippingCarriers;
    public Collection $shippingMethods;
    public Collection $shippingBoxes;
    public Collection $warehouses;
    public Collection $automations;

    /**
     * @param Collection $customers
     * @param Collection $orderStatuses
     * @param Collection $shippingCarriers
     * @param Collection $shippingMethods
     * @param Collection $shippingBoxes
     * @param Collection $warehouses
     * @param Collection $automations
     */
    public function __construct(Collection $customers, Collection $orderStatuses, Collection $shippingCarriers, Collection $shippingMethods, Collection $shippingBoxes, Collection $warehouses, Collection $automations)
    {
        $this->customers = $customers;
        $this->orderStatuses = $orderStatuses;
        $this->shippingCarriers = $shippingCarriers;
        $this->shippingMethods = $shippingMethods;
        $this->shippingBoxes = $shippingBoxes;
        $this->warehouses = $warehouses;
        $this->automations = $automations;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'customers' => $this->customers,
            'order_statuses' => $this->orderStatuses,
            'shipping_carriers' => $this->shippingCarriers,
            'shipping_methods' => $this->shippingMethods,
            'shipping_boxes' => $this->shippingBoxes,
            'warehouses' => $this->warehouses,
            'automations' => $this->automations,
        ];
    }
}
