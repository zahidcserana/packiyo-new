<?php

namespace App\Http\Controllers;

use App\Reports\DuplicateBarcodeReport;
use App\Reports\InventorySnapshotReport;
use App\Reports\PackerReport;
use App\Reports\PickerReport;
use App\Reports\ReplenishmentReport;
use App\Reports\Report;
use App\Reports\ToteLogReport;
use App\Reports\ShipmentReport;
use App\Reports\ShippedItemReport;
use App\Reports\StaleInventoryReport;
use App\Reports\PickingBatchReport;
use App\Reports\ReturnedProductReport;
use App\Reports\LotInventoryReport;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

class ReportController extends Controller
{
    private const REPORTS = [
        'shipment' => ShipmentReport::class,
        'shipped_item' => ShippedItemReport::class,
        'picker' => PickerReport::class,
        'packer' => PackerReport::class,
        'replenishment' => ReplenishmentReport::class,
        'stale_inventory' => StaleInventoryReport::class,
        'picking_batch' => PickingBatchReport::class,
        'returned_product' => ReturnedProductReport::class,
        'lot_inventory' => LotInventoryReport::class,
        'tote_log' => ToteLogReport::class,
        'duplicate_barcode' => DuplicateBarcodeReport::class,
        'inventory_snapshot' => InventorySnapshotReport::class,
    ];

    public function view(Request $request, $reportId)
    {
        if ($report = $this->getReportInstance($reportId)) {
            return $report->view($request);
        }

        abort(404);
    }

    public function widgets(Request $request, $reportId)
    {
        if ($report = $this->getReportInstance($reportId)) {
            return $report->widgets($request);
        }

        abort(404);
    }

    public function dataTable(Request $request, $reportId)
    {
        if ($report = $this->getReportInstance($reportId)) {
            return $report->dataTable($request);
        }

        abort(404);
    }

    public function export(Request $request, $reportId)
    {
        if ($report = $this->getReportInstance($reportId)) {
            return $report->export($request);
        }

        abort(404);
    }

    private function getReportInstance($reportId): ?Report
    {
        $reportClass = Arr::get(self::REPORTS, $reportId);

        if ($reportClass) {
            return App::make($reportClass);
        }

        return null;
    }
}
