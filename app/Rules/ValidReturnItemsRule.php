<?php

namespace App\Rules;

use App\Models\OrderItem;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;

class ValidReturnItemsRule implements Rule, DataAwareRule
{
    protected $data = [];

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $items = $this->data['items'] ?? [];
        $items = empty($items) && isset($this->data['order_items']) ? $this->data['order_items'] : $items;

        $items = array_filter($items, static function ($item) {
            return isset($item['is_returned']) && $item['quantity'] > 0;
        });

        if (!count($items)) {
            return false;
        }

        if (isset($this->data['order_id'])) {
            $orderItems = OrderItem::where('order_id', $this->data['order_id'])
                ->groupBy('id')
                ->get()
                ->keyBy('id');
            
            foreach ($items as $key => $item) {
                if (empty($orderItems[$key]) || $orderItems[$key]['quantity_shipped'] < $item['quantity']) {                    
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return __('You must choose items that were shipped.');
    }
}
