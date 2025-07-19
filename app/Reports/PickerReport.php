<?php

namespace App\Reports;

use App\Http\Resources\ExportResources\PickerReportExportResource;
use App\Http\Resources\PickerReportTableResource;
use App\Models\Customer;
use App\Models\PickingBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PickerReport extends Report
{
    protected $reportId = 'picker';
    protected $dataTableResourceClass = PickerReportTableResource::class;
    protected $exportResourceClass = PickerReportExportResource::class;

    protected function reportTitle()
    {
        return __('Pickers');
    }

    protected function getQuery(Request $request): ?Builder
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'contact_informations.name';
        $sortDirection = 'asc';
        $filterInputs =  $request->get('filter_form');
        $term = Arr::get($request->get('search'), 'value');

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();
        $filterCustomerId = Arr::get($filterInputs, 'customer_id');

        if ($filterCustomerId && $filterCustomerId != 'all') {
            $filterCustomerIds = array_intersect($customerIds, [$filterCustomerId]);
            $customerIds = Customer::withClients($filterCustomerIds)->pluck('id')->toArray();
        }

        $query = PickingBatch::select(
                DB::raw('contact_informations.name as name'),
                DB::raw('SUM(picking_batch_items.quantity_picked) as items_count'),
                DB::raw('COUNT(DISTINCT order_items.product_id) as unique_items_count'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count')
            )
            ->join('picking_batch_items', function ($join) {
                $join->on('picking_batches.id', '=', 'picking_batch_items.picking_batch_id')
                    ->where('picking_batch_items.quantity_picked', '>', 0);
            })
            ->join('order_items', 'picking_batch_items.order_item_id', '=', 'order_items.id')
            ->join('tasks', 'picking_batches.id', '=', 'tasks.taskable_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('contact_informations', function ($join) {
                $join->on('tasks.user_id', '=', 'contact_informations.object_id')
                    ->where('object_type', User::class);
            })
            ->whereIn('orders.customer_id', $customerIds)
            ->when(!empty($filterInputs), function ($query) use ($filterInputs) {
                if (!empty($filterInputs['start_date']) || !empty($filterInputs['end_date'])) {
                    $startDate = Carbon::parse($filterInputs['start_date'] ?? '1970-01-01')->startOfDay();
                    $endDate = Carbon::parse($filterInputs['end_date'] ?? now())->endOfDay();

                    $query->whereBetween('picking_batch_items.updated_at', [$startDate, $endDate]);
                }
            })
            ->when(!empty($term), function ($query) use ($term) {
                $query->where('contact_informations.name', 'like', $term . '%');
            })
            ->groupBy(['contact_informations.id'])
            ->orderBy($sortColumnName, $sortDirection);

        if ($request->get('length') && ((int) $request->get('length')) !== -1) {
            $query = $query->skip($request->get('start'))->limit($request->get('length'));
        }

        return $query;
    }
}
