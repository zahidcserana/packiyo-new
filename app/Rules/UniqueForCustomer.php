<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class UniqueForCustomer implements Rule
{
    private $className;
    private $customerId;
    private $existingId;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($className, $customerId, $existingId = null)
    {
        $this->className = $className;
        $this->customerId = $customerId;
        $this->existingId = $existingId;
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
        $splitAttribute = explode('.', $attribute);
        $attribute = $splitAttribute[count($splitAttribute) - 1];

        $object = call_user_func(array($this->className, 'where'), [
            $attribute => $value,
            'customer_id' => $this->customerId
        ])->first();

        if (!$object || $object->id == $this->existingId) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return trans(':attribute already exists for customer');
    }
}
