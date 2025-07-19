<?php

namespace App\Rules\Product;

use App\Models\Product;
use Illuminate\Contracts\Validation\Rule;

class ProductTypeRule implements Rule
{
    private ?Product $product;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(int $productId)
    {
        $this->product = Product::findOrFail($productId);
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
        if (in_array($value, [Product::PRODUCT_TYPE_STATIC_KIT, Product::PRODUCT_TYPE_DYNAMIC_KIT])) {
            $isKitComponent = app('product')->existsAsComponent($this->product);

            if ($isKitComponent) {
                return false;
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
        return trans(':sku cannot be a kit - it\'s already a component.', ['sku' => $this->product->sku]);
    }
}
