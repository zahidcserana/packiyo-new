<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkPrintRequest;

class BulkPrintingController extends Controller
{
    public function bulkPrint(BulkPrintRequest $request)
    {
        return match ($request->column) {
            'order_slip' => app('bulkPrint')->bulkPrintOrderSlips($request),
            default => app('bulkPrint')->bulkPrintBarcodes($request)
        };
    }
}
