<?php

namespace App\Reports;

use App\Http\Resources\ExportResources\LotInventoryReportExportResource;
use App\Http\Resources\LotInventoryReportTableResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use App\Models\LotItem;
use App\Models\Warehouse;

class LotInventoryReport extends Report
{
    protected $reportId = 'lot_inventory';
    protected $dataTableResourceClass = LotInventoryReportTableResource::class;
    protected $exportResourceClass = LotInventoryReportExportResource::class;

    protected function reportTitle(): string
    {
        return __('Lot Inventories');
    }

    protected function getQuery(Request $request): ?Builder
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'lots.expiration_date';
        $sortDirection = 'desc';
        $filterInputs = $request->get('filter_form');
        $term = Arr::get($request->get('search'), 'value');

        $search = $request->get('search');


        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        if ($term = Arr::get($search, 'value')) {
            $filterInputs = [];
        }

        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $filterCustomerId = Arr::get($filterInputs, 'customer_id');

        if ($filterCustomerId && $filterCustomerId != 'all') {
            $customerIds = array_intersect($customerIds, [$filterCustomerId]);
        }

        $query = LotItem::with([
                'lot.product',
                'location.warehouse',
            ])
            ->leftJoin('lots', 'lot_items.lot_id', '=', 'lots.id')
            ->leftJoin('products', 'lots.product_id', '=', 'products.id')
            ->leftJoin('locations', 'lot_items.location_id', '=', 'locations.id')
            ->leftJoin('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
            ->join('contact_informations', 'warehouses.id', '=', 'contact_informations.object_id')
            ->where('products.type', Product::PRODUCT_TYPE_REGULAR)
            ->where('contact_informations.object_type', Warehouse::class)
            ->whereIn('lots.customer_id', $customerIds)
            ->when(!empty($filterInputs), static function (Builder $query) use ($filterInputs) {
                if (Arr::get($filterInputs, 'start_date') || Arr::get($filterInputs, 'end_date')) {
                    $startDate = Carbon::parse($filterInputs['start_date'] ?? '1970-01-01')->startOfDay();
                    $endDate = Carbon::parse($filterInputs['end_date'] ?? Carbon::now())->endOfDay();

                    $query->whereBetween('lots.expiration_date', [$startDate, $endDate]);
                }
                
                if (!empty($filterInputs['exclude_empty'])) {
                    $query->where('lot_items.quantity_remaining', '>', 0);
                }
            })
            ->when(!empty($term), static function (Builder $query) use ($term) {
                $term = $term . '%';

                $query->where('lots.name', 'like', $term)
                    ->orWhere('products.name', 'like', $term)
                    ->orWhere('products.sku', 'like', $term)
                    ->orWhere('locations.name', 'like', $term)
                    ->orWhere('contact_informations.name', 'like', $term);
            })
            ->when(!empty($term), static function (Builder $query) use ($term) {
                $term = $term . '%';

                $query->where(static function (Builder $query) use ($term) {
                    $query->where('products.name', 'like', $term)
                        ->orWhere('products.sku', 'like', $term);
                });
            })
            ->select('lot_items.*')
            ->groupBy('lot_items.id')
            ->orderBy($sortColumnName, $sortDirection);

        return $query;
    }
}
