<?php

namespace App\JsonApi\PublicV1\PurchaseOrders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;
use App\Http\Requests\PurchaseOrder\StoreRequest;
use App\Http\Requests\PurchaseOrder\UpdateRequest;

class PurchaseOrderRequest extends ResourceRequest
{

    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        if ($this->isCreating()) {
            $request = new StoreRequest($this->input('data.attributes'));
        } else {
            $request = new UpdateRequest($this->input('data.attributes'));
        }

        $rules = $request->rules();

        unset($rules['customer_id']);
        $rules['customer'] = JsonApiRule::toOne();

        return $rules;
    }


    public function prepareForValidation()
    {
        parent::prepareForValidation();

        $input = $this->input();

        $customerId = $this->input('data.relationships.customer.data.id');
        $input['data']['attributes']['customer_id'] = $customerId;

        $this->setPurchaseOrderItems($customerId, $input);
        $this->setWarehouse($customerId, $input);
        $this->setSupplier($customerId, $input);

        if ($tagsArray = $this->setTags(Arr::get($input, 'data', []))) {
            $input['data']['attributes']['tags'] = $tagsArray;
        }

        $this->replace($input);
    }

    protected function withExisting(PurchaseOrder $purchaseOrder, array $existing)
    {
        if ($tagsArray = $this->setTags($existing)) {
            $existing['attributes']['tags'] = $tagsArray;
        }

        return $existing;
    }

    private function setPurchaseOrderItems(int $customerId, array &$input): void
    {
        if ($purchaseOrderItemData = Arr::get($input, 'data.attributes.purchase_order_items_data')) {
            $purchaseOrder = $this->model();

            foreach ($purchaseOrderItemData as $key => $purchaseOrderItem) {
                $product = Product::where('customer_id', $customerId)
                    ->where('sku', $purchaseOrderItem['sku'])
                    ->firstOrFail();

                $purchaseOrderItemData[$key]['product_id'] = $product->id;
                $purchaseOrderItemData[$key]['has_quantity_changed'] = false;

                if (! $purchaseOrder) {
                    continue;
                }

                if ($purchaseOrderItem['quantity'] ?? false) {
                    $originalPurchaseOrderItem = PurchaseOrderItem::query()
                        ->where('purchase_order_id', $purchaseOrder->id)
                        ->where('product_id', $product->id)
                        ->first(['id', 'quantity', 'quantity_received']);

                    $purchaseOrderItemData[$key]['has_quantity_changed'] = $originalPurchaseOrderItem->quantity != $purchaseOrderItem['quantity'];
                }
            }
            $input['data']['attributes']['purchase_order_items'] = $purchaseOrderItemData;
            unset($input['data']['attributes']['purchase_order_items_data']);
        }
    }

    /**
     * @param int $customerId
     * @param array $input
     * @return void
     */
    private function setSupplier(int $customerId, array &$input): void
    {
        $supplierName = Arr::get($input, 'data.attributes.supplier_name');

        $supplier = Supplier::whereCustomerId($customerId)->whereHas('contactInformation', function (Builder $query) use ($supplierName) {
            $query->where('name', $supplierName);
        })->first();

        if ($supplier) {
            $input['data']['attributes']['supplier_id'] = $supplier->id;
        } else {
            $input['data']['attributes']['supplier_id'] = null;
        }

        unset($input['data']['attributes']['supplier_name']);
    }

    /**
     * @param int $customerId
     * @param array $input
     * @return void
     */
    private function setWarehouse(int $customerId, array &$input): void
    {
        $warehouseName = Arr::get($input, 'data.attributes.warehouse_name');

        $customerIds = [$customerId];
        $customer = Customer::find($customerId);

        if ($customer->parent_id) {
            $customerIds[] = $customer->parent_id;
        }

        $warehouse = Warehouse::whereIn('customer_id', $customerIds)
            ->whereHas('contactInformation', function (Builder $query) use ($warehouseName) {
                $query->where('name', $warehouseName);
            })
            ->first();

        if ($warehouse) {
            $input['data']['attributes']['warehouse_id'] = $warehouse->id;
        }

        unset($input['data']['attributes']['warehouse_name']);
    }

    /**
     * @param array $input
     * @return array|null
     */
    private function setTags(array $input): array|null
    {
        $tags = Arr::get($input, 'attributes.tags');

        if ($tags != '') {
            $tagsData = array_map('trim', explode(',', $tags));

            return $tagsData;
        }

        return null;
    }
}
