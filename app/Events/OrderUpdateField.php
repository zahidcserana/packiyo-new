<?php

namespace App\Events;

use App\Models\Order;

enum OrderUpdateField: string
{
    case ShippingMethod = 'shipping_method_id';

    public function hasChange(Order $order): bool
    {
        return match($this) {
            OrderUpdateField::ShippingMethod => $order->wasChanged($this->value),
        };
    }
}
