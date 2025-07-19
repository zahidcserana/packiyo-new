<?php

namespace App\Components;

use App\Http\Requests\BulkPrintRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDF;
use Picqer\Barcode\BarcodeGeneratorPNG;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfReader\PageBoundaries;
use setasign\Fpdi\PdfReader\PdfReaderException;
use setasign\Fpdi\Tcpdf\Fpdi;
use Webpatser\Countries\Countries;

class SiteComponent extends BaseComponent
{
    public function filterCountries(Request $request): JsonResponse
    {
        $term = "%{$request->get('term')}%";

        $results = Countries::where('name', 'LIKE', $term);

        if ($results->count() === 0) {
            $results = Countries::query();
        }

        $results = $results->get()
            ->map(fn($country) => [
                'id' => $country->id,
                'text' => $country->name,
                'country_code' => $country->iso_3166_2
            ]);

        return response()->json([
            'results' => $results
        ]);
    }
}
