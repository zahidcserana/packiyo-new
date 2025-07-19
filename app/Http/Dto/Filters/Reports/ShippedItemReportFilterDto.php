<?php

namespace App\Http\Dto\Filters\Reports;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class ShippedItemReportFilterDto implements Arrayable
{
    public Collection $shippingCarriers;
    public Collection $shippingMethods;
    public Collection $orderChannels;
    public Collection $shippingBoxes;

    /**
     * @param Collection $shippingCarriers
     * @param Collection $shippingMethods
     * @param Collection $orderChannels
     * @param Collection $shippingBoxes
     */
    public function __construct(Collection $shippingCarriers, Collection $shippingMethods, Collection $orderChannels, Collection $shippingBoxes)
    {
        $this->shippingCarriers = $shippingCarriers;
        $this->shippingMethods = $shippingMethods;
        $this->orderChannels = $orderChannels;
        $this->shippingBoxes = $shippingBoxes;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'shipping_carriers' => $this->shippingCarriers,
            'shipping_methods' => $this->shippingMethods,
            'order_channels' => $this->orderChannels,
            'shipping_boxes' => $this->shippingBoxes,
        ];
    }
}
