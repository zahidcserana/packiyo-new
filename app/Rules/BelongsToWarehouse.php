<?php

namespace App\Rules;

use App\Features\MultiWarehouse;
use App\Models\Warehouse;
use Illuminate\Contracts\Validation\Rule;
use Laravel\Pennant\Feature;

class BelongsToWarehouse implements Rule
{
    private Warehouse $warehouse;

    private string $className;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(Warehouse $warehouse, string $className)
    {
        $this->warehouse = $warehouse;
        $this->className = $className;
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
        if (Feature::for('instance')->inactive(MultiWarehouse::class)) {
            return true;
        }

        $object = call_user_func(array($this->className, 'find'), $value);

        return $object && $object->warehouse_id === $this->warehouse->id;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return trans(':attribute does not belong to the same warehouse!');
    }
}
