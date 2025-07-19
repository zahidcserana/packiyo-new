<?php

namespace App\Rules;

use App\Models\OrderItem;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;

class DistinctRateCardsRule implements Rule, DataAwareRule
{
    protected $data = [];

    protected const PRIMARY_RATE_CARD_ID_KEY = 'primary_rate_card_id';
    protected const SECONDARY_RATE_CARD_ID_KEY = 'secondary_rate_card_id';

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
    public function passes($attribute, $value)
    {
        if (!array_key_exists(self::PRIMARY_RATE_CARD_ID_KEY, $this->data) || !array_key_exists(self::SECONDARY_RATE_CARD_ID_KEY, $this->data)) {
            return false; // Only applies when both are present.
        }

        if (empty($this->data[self::PRIMARY_RATE_CARD_ID_KEY]) || empty($this->data[self::SECONDARY_RATE_CARD_ID_KEY])) {
            return true; // If either is null, we're cool.
        }

        if ($this->data[self::PRIMARY_RATE_CARD_ID_KEY] == $this->data[self::SECONDARY_RATE_CARD_ID_KEY]) {
            return false; // They should be distinct from each other.
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('Please choose a different secondary rate card.');
    }
}
