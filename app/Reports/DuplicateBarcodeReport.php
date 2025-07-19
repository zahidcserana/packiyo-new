<?php

namespace App\Reports;

use App\Http\Resources\DuplicateBarcodeReportTableResource;
use App\Http\Resources\ExportResources\DuplicateBarcodeReportExportResource;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DuplicateBarcodeReport extends Report
{
    protected $reportId = 'duplicate_barcode';
    protected $dataTableResourceClass = DuplicateBarcodeReportTableResource::class;
    protected $exportResourceClass = DuplicateBarcodeReportExportResource::class;

    protected function reportTitle()
    {
        return __('Duplicate Barcodes');
    }

    protected function getQuery(Request $request): ?Builder
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'products.id';
        $sortDirection = 'desc';

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        return Product::select('barcode', DB::raw('GROUP_CONCAT(sku) AS product_skus'), 'customer_contact_information.name AS customer_name', 'customers.id AS customer_id')
            ->join('customers', 'products.customer_id', '=', 'customers.id')
            ->join('contact_informations AS customer_contact_information', 'customers.id', '=', 'customer_contact_information.object_id')
            ->where('customer_contact_information.object_type', Customer::class)
            ->whereIn('products.customer_id', $customerIds)
            ->groupBy('customer_id', 'barcode')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy($sortColumnName, $sortDirection);
    }
}
