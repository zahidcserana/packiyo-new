<?php

namespace App\Rules;

use App\Models\ProductBarcode;
use Illuminate\Contracts\Validation\Rule;

class UniqueProductBarcode implements Rule
{
    private $className;
    private $productId;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($className, $productId)
    {
        $this->className = $className;
        $this->productId = $productId;
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
        $productBarcodes = ProductBarcode::whereBarcode($value)
            ->where('product_id', $this->productId)
            ->orWhereHas('product', function ($query) use ($value) {
                $query
                    ->where('barcode', $value)
                    ->where('product_id', $this->productId);
            })
            ->first();

        if (!$productBarcodes || $this->productId == $productBarcodes->product_id) {
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
        return trans('Barcode already exists for product');
    }
}
