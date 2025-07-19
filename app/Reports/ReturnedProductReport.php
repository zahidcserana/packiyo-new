<?php

namespace App\Reports;

use App\Http\Resources\ExportResources\ReturnedProductReportExportResource;
use App\Http\Resources\ReturnedProductReportTableResource;
use App\Models\ReturnItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReturnedProductReport extends Report
{
    protected $reportId = 'returned_product';
    protected $dataTableResourceClass = ReturnedProductReportTableResource::class;
    protected $exportResourceClass = ReturnedProductReportExportResource::class;

    protected function reportTitle()
    {
        return __('Returned Products');
    }

    protected function getQuery(Request $request): ?Builder
    {
        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'return_items.created_at';
        $sortDirection = 'desc';
        $filterInputs = $request->get('filter_form');
        $term = Arr::get($request->get('search'), 'value');

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $query = ReturnItem::query()
            ->leftJoin('products', 'return_items.product_id', '=', 'products.id')
            ->leftJoin('returns', 'return_items.return_id', '=', 'returns.id')
            ->leftJoin('orders', 'returns.order_id', '=', 'orders.id')
            ->whereIn('orders.customer_id', $customerIds)
            ->when(!empty($filterInputs), static function (Builder $query) use ($filterInputs) {
                if (Arr::get($filterInputs, 'start_date') || Arr::get($filterInputs, 'end_date')) {
                    $startDate = Carbon::parse($filterInputs['start_date'] ?? '1970-01-01')->startOfDay();
                    $endDate = Carbon::parse($filterInputs['end_date'] ?? Carbon::now())->endOfDay();

                    $query->whereBetween('return_items.created_at', [$startDate, $endDate]);
                }
            })
            ->when(!empty($term), static function(Builder $query) use ($term) {
                $term = $term . '%';

                $query->where(static function ($query) use ($term) {
                    $query->whereHas('product', static function ($query) use ($term) {
                        $query->where('name', 'like', $term)
                            ->orWhere('sku', 'like', $term);
                    })
                    ->orWhereHas('return_.order', static function ($query) use ($term) {
                        $query->where('number', 'like', $term);
                    });
                });
            })
            ->select(DB::raw('products.id as product_id, products.sku as product_sku, SUM(quantity) as quantity_requested, SUM(quantity_received) as quantity_returned, COUNT(DISTINCT(orders.id)) AS orders_returned'))
            ->groupBy('products.sku')
            ->orderBy($sortColumnName, $sortDirection);

        return $query;
    }
}
