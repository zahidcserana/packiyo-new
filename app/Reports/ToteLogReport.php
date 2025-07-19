<?php

namespace App\Reports;

use App\Http\Dto\Filters\Reports\ToteLogReportFilterDto;
use App\Http\Resources\ExportResources\ToteLogReportExportResource;
use App\Http\Resources\ToteLogReportTableResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class ToteLogReport extends Report
{
    protected $reportId = 'tote_log';
    protected $dataTableResourceClass = ToteLogReportTableResource::class;
    protected $exportResourceClass = ToteLogReportExportResource::class;

    protected function reportTitle()
    {
        return __('Tote Log');
    }

    protected function getQuery(Request $request): ?Builder
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'tote_order_items.updated_at';
        $sortDirection = 'desc';
        $filterInputs = $request->get('filter_form');

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $toteItemsCollection = app('tote')->getToteItemsQuery($filterInputs, $sortColumnName, $sortDirection);

        $customers = app()->user->getSelectedCustomers()->pluck('id')->toArray();

        $toteItemsCollection = $toteItemsCollection->whereIn('orders.customer_id', $customers);

        $term = $request->get('search')['value'];

        if ($term) {
            $term = '%' . $term . '%';

            $toteItemsCollection->where(function ($q) use ($term) {
                $q->where('orders.number', 'like', $term)
                    ->orWhere('products.sku', 'like', $term)
                    ->orWhere('totes.name', 'like', $term);
            });
        }

        return $toteItemsCollection;
    }

    protected function getFilterDto(Request $request): ?Arrayable
    {
        $users = new Collection();

        foreach (app('user')->getSelectedCustomers() as $customer) {
            foreach ($customer->users as $user) {
                $users->add([
                    'id' => $user->id,
                    'name' => $user->contactInformation->name
                ]);
            }
        }

        return new ToteLogReportFilterDto($users);
    }
}
