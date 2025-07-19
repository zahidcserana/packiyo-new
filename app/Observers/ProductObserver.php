<?php

namespace App\Observers;

use App\Components\OrderComponent;
use App\Jobs\OrderItem\UpdateProductInOrderItemsJob;
use App\Models\Product;
use App\Models\OrderItem;

class ProductObserver
{
    public function saved(Product $product): void
    {
        if ($product->wasChanged(['quantity_reserved'])) {
            if (method_exists(app('allocation'), 'recalculateQuantityAvailable')) {
                app('allocation')->recalculateQuantityAvailable(Product::find($product->id));
            }
        }

        if ($product->wasChanged(['quantity_available', 'quantity_backordered'])) {
            app('inventoryLog')->triggerAdjustInventoryWebhook($product);
        }
    }

    public function created(Product $product): void
    {
        UpdateProductInOrderItemsJob::dispatch($product);
    }

    public function updated(Product $product): void
    {
        if ($product->wasChanged('sku')) {
            OrderItem::query()->where('product_id', $product->id)
                ->update([
                    'sku' => $product->sku
                ]);

            UpdateProductInOrderItemsJob::dispatch($product);
        }

        if ($product->wasChanged(['weight', 'height', 'length', 'width'])) {
            $dimensions = ['weight', 'height', 'length', 'width'];
            $productChanges = $product->getChanges();

            $dimensions = array_filter($dimensions, static function ($item) use ($productChanges) {
                if (array_key_exists($item, $productChanges)) {
                    return $item;
                }
            });

            $updateColumns = [];

            foreach ($dimensions as $dimension) {
                $updateColumns[$dimension] = (float) $product->$dimension;
            }

            OrderItem::query()->where('product_id', $product->id)
                ->where('quantity_pending', '>', 0)
                ->update($updateColumns);
        }

        if ($product->wasChanged(['type'])) {
            $product = $product->refresh();

            app('allocation')->allocateInventory($product);
        }
    }
}
