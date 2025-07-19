<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class InventoryCsvRowValidationRule implements Rule
{
    protected $validColumns = ['sku', 'location', 'lot_name', 'quantity', 'action (replace, increase, decrease)'];
    protected $allowedActionValues = ['replace', 'increase', 'decrease'];
    public const COLUMN_QUANTITY = '2';

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $csvData = array_map('str_getcsv', file($value->getPathname()));
        $csvData = array_slice($csvData, 1);

        foreach ($csvData as $row) {
            if (count($row) !== count($this->validColumns)) {
                return false;
            }

            foreach ($row as $key => $column) {
                if (empty($column)) {
                    if ($key == self::COLUMN_QUANTITY && $column == '0') {
                        return true;
                    }

                    return false;
                }

                if ($key === array_key_last($row)) {
                    return in_array($column, $this->allowedActionValues);
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
    public function message()
    {
        return 'CSV file rows are not valid.';
    }
}
