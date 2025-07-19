<?php

namespace App\Rules;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductBarcode;
use Illuminate\Contracts\Validation\Rule;

class IsDuplicateBarcode implements Rule
{
    private $customer;
    private $productId;

    public $duplicates = [];

    private const APPLY_ON_ROUTES = [
        'product.store',
        'product.update'
    ];

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($customerId, $productId = null)
    {
        $this->customer = Customer::find($customerId);

        if (!is_null($productId)) {
            $this->productId = $productId;
        }
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
        $routeName = \Request::route() ? \Request::route()->getName() : null;

        if (is_null($value) || (!in_array($routeName, self::APPLY_ON_ROUTES) && !is_null($routeName))) {
            return true;
        }

        if (is_null($this->productId)) {
            $products = Product::whereBarcode($value)
                ->where('customer_id', $this->customer->id)
                ->orWhere(function ($query) use ($value) {
                    $query->where('customer_id', $this->customer->id)
                        ->whereHas('productBarcodes', function ($query) use ($value) {
                            $query->where('barcode', $value);
                        });
                })
                ->get();
        } else {
            $product = Product::find($this->productId);

            if ($product->barcode !== $value) {
                $products = Product::whereBarcode($value)
                    ->where('customer_id', $this->customer->id)
                    ->where('id', '<>', $this->productId)
                    ->orWhere(function ($query) use ($value) {
                        $query->where('customer_id', $this->customer->id)
                            ->where('id', '<>', $this->productId)
                            ->whereHas('productBarcodes', function ($query) use ($value) {
                                $query->where('barcode', $value);
                            });
                    })
                    ->get();
            }
        }

        if (!isset($products) || $products->isEmpty()) {
            return true;
        }

        foreach ($products as $product) {
            $this->duplicates[] = $product->sku;
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
        return trans('The following products have the same barcode: :duplicates', [
            'duplicates' => implode(', ', $this->duplicates)
        ]);
    }
}
