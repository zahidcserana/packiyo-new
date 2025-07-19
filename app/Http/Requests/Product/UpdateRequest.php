<?php

namespace App\Http\Requests\Product;

use App\Features\PreventDuplicateBarcodes;
use App\Http\Requests\FormRequest;
use App\Models\Customer;
use App\Rules\CanChangeLotTracking;
use App\Models\Product;
use App\Rules\IsDuplicateBarcode;
use App\Rules\Product\ProductTypeRule;
use Illuminate\Support\Arr;
use Laravel\Pennant\Feature;

class UpdateRequest extends FormRequest
{
    public static function validationRules(): array
    {
        // TODO: figure out this mess
        $product = request()->route('product');

        if (is_string($product)) {
            $productId = $product;
            $product = null;
        }

        if (empty($productId)) {
            $productId = self::$recordId;
        }

        if (empty($productId)) {
            $productId = self::getInputField('id');
        }

        if (!$product) {
            $product = Product::withTrashed()->find($productId);
        }

        $productLocationsArray = array_filter(self::$formRequest->product_locations ?? [], function ($location) {
            return ($location['quantity'] ?? 0) != 0;
        });

        $rules = StoreRequest::validationRules();

        $rules['id'] = [
            'nullable'
        ];

        if (isset($rules['customer_id'])) {
            unset($rules['customer_id']);
        }

        if (isset($rules['barcode'])) {
            $customer = Customer::find($product->customer_id);

            if ((Feature::for($customer)->active(PreventDuplicateBarcodes::class) || Feature::for('instance')->active(PreventDuplicateBarcodes::class)) && $productId) {
                $rules['barcode'][] = new IsDuplicateBarcode($customer->id, $productId);
            }
        }

        $rules['sku'] = ['sometimes'];
        $rules['name'] = ['sometimes'];

        $rules['lot_tracking'][] = new CanChangeLotTracking($product);

        foreach ($rules['price'] as $key => $rule) {
            if (str_contains($rule, 'required')) {
                $rules['price'][$key] = 'nullable';
                break;
            }
        }

        $rules['type'] = ['sometimes', new ProductTypeRule($product->id)];

        $rules['product_locations'] = [
            'array',
        ];

        $rules['product_lots'] = [
            'array',
            self::$formRequest->product?->lot_tracking === 1 && count($productLocationsArray)
                ? 'required_if:lot_tracking,1'
                : '',
            'min:' . count($productLocationsArray),
        ];

        $rules['product_locations.*.id'] = [
            'sometimes',
            'numeric',
        ];

        $rules['product_lots.*.id'] = [
            'sometimes',
            'required_unless:product_locations.*.quantity,0',
            'exists:lots,id',
            'nullable',
        ];

        $rules['product_locations.*.quantity'] = [
            'sometimes',
            'numeric'
        ];

        $rules['update_orders'] = ['sometimes', 'boolean'];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'product_lots.min' => __('Not all locations have Lot selected!'),
            'product_lots.*.id.required_unless' => __('The Lot Name field is required because this product has lot tracking enabled.'),
            'kit_items.*.id.required_if' => __(':attribute does not exist'),
            'kit_items.*.id.exists' => __(':attribute cannot be a component - it\'s already a kit.')
        ];
    }

    protected function prepareForValidation()
    {
        $input = $this->all();

        if ($kitItems = Arr::get($input, 'kit_items')) {
            if ($product = Product::find(self::$recordId)) {
                foreach ($kitItems as &$kitItem) {
                    if (!Arr::has($kitItem, 'id') && $componentSku = Arr::get($kitItem, 'sku')) {
                        $component = Product::where('customer_id', $product->customer_id)
                            ->where('sku', $componentSku)
                            ->first();

                        if ($component) {
                            $kitItem['id'] = $component->id;
                        }
                    }
                }

                $this->merge(['kit_items' => $kitItems]);
            }
        }
    }
}
