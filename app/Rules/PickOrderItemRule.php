<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class PickOrderItemRule implements Rule
{
    private $className;
    private $model;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($className, $model)
    {
        $this->className = $className;
        $this->model = $model;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $orderItem = $this->className::findOrFail($value);

        return $this->model->placedToteOrderItems()->whereHas('orderItem', function ($query) use ($orderItem) {
            $query->where('order_id', '<>', $orderItem->order_id);
        })->doesntExist();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return trans(':attribute doesn\'t belong to Order that already assigned Tote');
    }
}
