<?php

namespace App\Components;

use App\Features\MultiWarehouse;
use App\Http\Requests\Csv\ExportCsvRequest;
use App\Http\Resources\ExportResources\InventoryLogExportResource;
use App\Http\Resources\InventoryLogResource;
use App\Jobs\AllocateInventoryJob;
use App\Models\{InventoryLog,
    Location,
    LocationProduct,
    Lot,
    LotItem,
    Product,
    Webhook};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryLogComponent extends BaseComponent
{
    use CalculatesOccupiedLocations;

    public const OPERATION_TYPE_MANUAL = 'manual';
    public const OPERATION_TYPE_SHIP = 'ship';
    public const OPERATION_TYPE_RESHIP = 'reship';
    public const OPERATION_TYPE_RECEIVE = 'receive';
    public const OPERATION_TYPE_TRANSFER = 'transfer';
    public const OPERATION_TYPE_CYCLE_COUNT = 'cycle_count';
    public const OPERATION_TYPE_MANUAL_INCREASE = 'increase';
    public const OPERATION_TYPE_MANUAL_DECREASE = 'decrease';

    public const REASONS = [
        self::OPERATION_TYPE_MANUAL => 'Adjusted manually',
        self::OPERATION_TYPE_SHIP => 'Shipped',
        self::OPERATION_TYPE_RESHIP => 'Reshipped',
        self::OPERATION_TYPE_RECEIVE => 'Received',
        self::OPERATION_TYPE_TRANSFER => 'Transferred',
        self::OPERATION_TYPE_CYCLE_COUNT => 'Cycle Count',
        self::OPERATION_TYPE_MANUAL_INCREASE => 'Increase',
        self::OPERATION_TYPE_MANUAL_DECREASE => 'Decrease',
    ];

    public function adjustInventory(
        Location $location,
        Product  $product,
        int      $quantity,
        string   $operation,
        Model    $associatedObject = null,
        Lot      $lot = null
    ): ?InventoryLog {
        $locationProduct = $this->getLocationProduct($location, $product);

        if ($product->lot_tracking && !$lot) {
            $lot = $product->lots()
                ->whereHas('placedLotItems', fn(Builder $query) => $query->where('location_id', $location->id))
                ->first();
        }

        if ($operation === self::OPERATION_TYPE_MANUAL || $operation == self::OPERATION_TYPE_CYCLE_COUNT) {
            if ($product->lot_tracking) {
                $quantity -= LotItem::where('lot_id', $lot->id ?? null)
                    ->where('product_id', $product->id)
                    ->where('location_id', $location->id)
                    ->sum('quantity_remaining');
            } else {
                $quantity -= $locationProduct->quantity_on_hand;
            }
        }

        if ($operation == self::OPERATION_TYPE_MANUAL_DECREASE) {
            $quantity = -$quantity;
        }

        if ($operation === self::OPERATION_TYPE_TRANSFER && $associatedObject instanceof Location) {
            $associatedLocationProduct = $this->getLocationProduct($associatedObject, $product);

            if ($product->lot_tracking && !$lot) {
                $lot = $product->lots()
                    ->whereHas(
                        'placedLotItems',
                        fn (Builder $query) => $query->where('location_id', $associatedObject->id)
                    )
                    ->first();
            }

            $this->updateLocationProduct($associatedLocationProduct, -$quantity, $lot);
            $this->createLogEntry($associatedLocationProduct, -$quantity, $operation, $location);
        }

        if (Feature::for('instance')->active(MultiWarehouse::class)) {
            $warehouse = $locationProduct->location->warehouse;
        } else {
            $warehouse = null;
        }

        $locationProduct = $this->updateLocationProduct($locationProduct, $quantity, $lot);
        $inventoryLog = $this->createLogEntry($locationProduct, $quantity, $operation, $associatedObject);

        if ($inventoryLog) {
            if ($operation == self::OPERATION_TYPE_TRANSFER) {
                AllocateInventoryJob::dispatch($locationProduct->product, $warehouse)->onQueue('allocation-high');
            } else {
                AllocateInventoryJob::dispatch($locationProduct->product, $warehouse);
            }
        }

        return $inventoryLog;
    }

    /*
     * Temporary solution to trigger webhook after the product is reallocated
     */
    public function triggerAdjustInventoryWebhook(Product $product, InventoryLog $inventoryLog = null)
    {
        if (!$inventoryLog || $product->isKit()) {
            $inventoryLog = new InventoryLog();

            $inventoryLog->user_id = auth()->user()->id ?? 1;
            $inventoryLog->product_id = $product->id;
            $inventoryLog->new_on_hand = $product->quantity_on_hand;
            $inventoryLog->reason = self::REASONS[self::OPERATION_TYPE_MANUAL];
        }

        $this->webhook(new InventoryLogResource($inventoryLog), get_class($inventoryLog), Webhook::OPERATION_TYPE_STORE, $inventoryLog->product->customer_id);
    }

    /**
     * @param ExportCsvRequest $request
     * @param array|null $filterInput
     * @return StreamedResponse
     */
    public function exportInventory(ExportCsvRequest $request, array $filterInput = null): StreamedResponse
    {
        $input = $request->validated();
        $search = $input['search']['value'];

        $csvFileName = Str::kebab(auth()->user()->contactInformation->name) . '-export-inventory-logs.csv';

        $inventoryLogCollection = $this->getQuery($request->get('filter_form'));

        if ($search) {
            $inventoryLogCollection = $this->searchQuery($search, $inventoryLogCollection);
        }

        return app('csv')->export($request, $inventoryLogCollection->get(), InventoryLogExportResource::columns(), $csvFileName, InventoryLogExportResource::class);
    }

    protected function getLocationProduct(Location $location, Product $product)
    {
        LocationProduct::firstOrCreate(
            [
                'product_id' => $product->id,
                'location_id' => $location->id
            ]
        );

        // Seems like a bug or unexpected behaviour when trying to use LocationProduct::firstOrCreate
        // Because it won't return the pivot data or even the ID.
        // Using hacky solution to refetch the same model that we just created
        return LocationProduct::where('location_id', $location->id)
            ->where('product_id', $product->id)
            ->first();
    }

    protected function createLogEntry(LocationProduct $locationProduct,
                                      $quantity,
                                      $operation,
                                      $associatedObject): ?InventoryLog
    {
        if ($quantity == 0) {
            return null;
        }

        $inventoryLog = new InventoryLog();

        if (!is_null($associatedObject)) {
            $inventoryLog->associatedObject()->associate($associatedObject);
        }

        $inventoryLog->user_id = auth()->user()->id ?? 1;
        $inventoryLog->product_id = $locationProduct->product->id;
        $inventoryLog->location_id = $locationProduct->location->id;
        $inventoryLog->previous_on_hand = $locationProduct->quantity_on_hand - $quantity;
        $inventoryLog->new_on_hand = $locationProduct->quantity_on_hand;
        $inventoryLog->quantity = $quantity;
        $inventoryLog->reason = self::REASONS[$operation];

        $inventoryLog->save();

        return $inventoryLog;
    }

    protected function updateLocationProduct(LocationProduct $locationProduct, $quantity, ?Lot $lot): LocationProduct
    {
        if ($lot) {
            $lotItem = LotItem::firstOrCreate([
                'lot_id' => $lot->id,
                'location_id' => $locationProduct->location_id
            ]);

            if ($quantity > 0) {
                $lotItem->increment('quantity_added', abs($quantity));
            } else {
                $lotItem->increment('quantity_removed', abs($quantity));
            }

            $lotItem->save();

            $locationOnHand = LotItem::where('product_id', $locationProduct->product_id)
                ->where('location_id', $locationProduct->location_id)
                ->sum('quantity_remaining');

            $locationProduct->update(['quantity_on_hand' => $locationOnHand]);

            // remove location product association for lot tracked products when the location becomes empty
            if ($locationProduct->quantity_on_hand == 0) {
                $locationProduct->delete();
            }

            if ($lotItem->quantity_remaining == 0) {
                $lotItem->delete();
            }

        } else {
            $locationProduct->increment('quantity_on_hand', $quantity);
        }

        return $locationProduct;
    }

    /**
     * @param $filterInputs
     * @param string $sortColumnName
     * @param string $sortDirection
     * @return Builder
     */
    public function getQuery($filterInputs, string $sortColumnName = 'inventory_logs.created_at', string $sortDirection = 'desc'): Builder
    {
        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $filterCustomerId = Arr::get($filterInputs, 'customer_id');

        if ($filterCustomerId && $filterCustomerId != 'all') {
            $customerIds = array_intersect($customerIds, [$filterCustomerId]);
        }

        $inventoryLogCollection = InventoryLog::with(['product', 'location', 'user.contactInformation', 'product.customer.contactInformation', 'location.warehouse.contactInformation', 'associatedObject'])
            ->join('products', 'inventory_logs.product_id', '=', 'products.id')
            ->whereIn('products.customer_id', $customerIds)
            ->where(function ($query) use ($filterInputs) {
                // Find by filter result
                // Start/End date
                if (!is_null($filterInputs)) {
                    if (Arr::get($filterInputs, 'start_date') || Arr::get($filterInputs, 'end_date')) {
                        $startDate = Carbon::parse($filterInputs['start_date'] ?? '1970-01-01')->startOfDay();
                        $endDate = Carbon::parse($filterInputs['end_date'] ?? Carbon::now())->endOfDay();
                        $query->whereBetween('inventory_logs.created_at', [$startDate, $endDate]);
                    }
                    // Product
                    if ($filterInputs['product'] != '') {
                            $query->where(function ($query) use ($filterInputs) {
                                $query->where('products.name', 'like', $filterInputs['product'] . '%');
                                $query->orWhere('products.sku', 'like', $filterInputs['product'] . '%');
                        });
                    }
                    // Customer
                    if ($filterInputs['reason'] != '0') {
                        $query->where('inventory_logs.reason', '=', $filterInputs['reason']);
                    }

                    // Warehouse
                    if ($filterInputs['warehouse'] != '0') {
                        $query->where('locations.warehouse_id', $filterInputs['warehouse']);
                    }

                    // Location
                    if ($filterInputs['location'] != '') {
                        $query->where('locations.name', 'like', $filterInputs['location'] . '%');
                    }

                    // Change by
                    if ($filterInputs['change_by'] != '0') {
                        $query->where('inventory_logs.user_id', $filterInputs['change_by']);
                    }
                }
            })
            ->select('inventory_logs.*')
            ->groupBy('inventory_logs.id')
            ->orderBy($sortColumnName, $sortDirection);

        if ($filterInputs['warehouse'] != '0' || $filterInputs['location'] != '') {
            $inventoryLogCollection->join('locations', 'inventory_logs.location_id', '=', 'locations.id');
        }

        return $inventoryLogCollection;
    }

    /**
     * @param string $term
     * @param $inventoryLogCollection
     * @return mixed
     */
    public function searchQuery(string $term, $inventoryLogCollection)
    {
        $term = $term . '%';

        return $inventoryLogCollection->where(static function (Builder $query) use ($term) {
            $query->where('products.name', 'like', $term)
                ->orWhere('sku', 'like', $term);
        });
    }
}
