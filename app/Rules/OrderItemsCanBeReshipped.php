<?php

namespace App\Rules;

use App\Models\OrderItem;
use Arr;
use Illuminate\Contracts\Validation\Rule;

class OrderItemsCanBeReshipped implements Rule
{
    private $failedSkus = [];

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        foreach ($value as $item) {
            $orderItemId = Arr::get($item, 'order_item_id');

            if ($orderItemId) {
                $orderItem = OrderItem::findOrFail($orderItemId);
                $quantityToReship = Arr::get($item, 'quantity');

                if ($orderItem->quantity_reshippable < $quantityToReship) {
                    $this->failedSkus[] = $orderItem->sku;
                }
            }
        }

        return empty($this->failedSkus);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('Cannot reship :failedSkus. Check the quantities', [
            'failedSkus' => implode(', ', $this->failedSkus)
        ]);
    }
}
