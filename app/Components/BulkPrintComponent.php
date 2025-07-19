<?php

namespace App\Components;

use App\Http\Requests\BulkPrintRequest;
use App\Models\Order;
use PDF;
use Illuminate\Support\{Facades\Log, Facades\Storage};
use Picqer\Barcode\BarcodeGeneratorPNG;
use setasign\Fpdi\{PdfParser\CrossReference\CrossReferenceException,
    PdfParser\Filter\FilterException,
    PdfParser\PdfParserException,
    PdfParser\Type\PdfTypeException,
    PdfReader\PageBoundaries,
    PdfReader\PdfReaderException,
    Tcpdf\Fpdi};

class BulkPrintComponent extends BaseComponent
{
    /**
     * @param BulkPrintRequest $request
     * @return never
     */
    public function bulkPrintBarcodes(BulkPrintRequest $request)
    {
        $user = $request->user();
        $modelFQN = 'App\\Models\\' . $request->model_name;

        if (!empty($request->relation)) {
            $records = $modelFQN::whereIn('id', $request->model_ids)->get()->pluck($request->relation)->unique();
        } else {
            $records = $modelFQN::whereIn('id', $request->model_ids)->get();
        }

        $generator = new BarcodeGeneratorPNG();
        $barcodes = [];

        $customer = app('user')->getSelectedCustomers()->first();

        if (!$customer) {
            return abort(400);
        }

        $paperWidth = paper_width($customer->id, 'barcode');
        $paperHeight = paper_height($customer->id, 'barcode');

        foreach ($records as $record) {
            $user->can('view', $record);

            $barcodes[] = [
                'name' => $record->name,
                'barcode' => $generator->getBarcode(
                    $record->barcode,
                    $generator::TYPE_CODE_128
                ),
                'number' => $record->barcode
            ];

            if ($request->model_name == 'Product') {
                foreach ($record->productBarcodes as $productBarcode) {
                    $barcodes[] = [
                        'name' => $record->name,
                        'barcode' => $generator->getBarcode(
                            $productBarcode->barcode,
                            $generator::TYPE_CODE_128
                        ),
                        'number' => $productBarcode->barcode
                    ];
                }
            }
        }

        $barcodes = array_unique($barcodes, SORT_REGULAR);

        return PDF::loadView('pdf.barcodes', [
            'barcodes' => $barcodes
        ])
            ->setPaper([0, 0, $paperWidth, $paperHeight])
            ->stream('barcodes.pdf');
    }

    /**
     * @throws CrossReferenceException
     * @throws PdfReaderException
     * @throws PdfParserException
     * @throws PdfTypeException
     * @throws FilterException
     */
    public function bulkPrintOrderSlips(BulkPrintRequest $request): string
    {
        $user = $request->user();
        $orders = Order::whereIn('id', $request->model_ids)->get();
        $customer = app('user')->getSelectedCustomers()->first();

        if (!$customer) {
            return abort(400);
        }

        $paperWidth = paper_width($customer->id, 'document');
        $paperHeight = paper_height($customer->id, 'document');

        $fpdi = new Fpdi('P', 'pt', array($paperWidth, $paperHeight));
        $fpdi->setPrintHeader(false);
        $fpdi->setPrintFooter(false);

        foreach ($orders as $order) {
            $user->can('view', $order);

            app('order')->generateOrderSlip($order);

            $pageCount = $fpdi->setSourceFile(Storage::path($order->order_slip));

            for ($i = 1; $i <= $pageCount; $i++) {
                try {
                    $fpdi->AddPage();
                    $tplId = $fpdi->importPage($i, PageBoundaries::ART_BOX);
                    $size = $fpdi->getTemplateSize($tplId);
                    $fpdi->useTemplate($tplId, $size);
                } catch (\Exception $exception) {
                    Log::error('Could not merge order slips - ' . $exception->getMessage());
                }
            }
        }

        return $fpdi->Output('order_slips.pdf');
    }
}
