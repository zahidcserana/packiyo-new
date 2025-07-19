<?php

namespace App\Http\Controllers;

use App\Http\Dto\Filters\ProductsDataTableDto;
use App\Http\Requests\Csv\{ExportCsvRequest, ImportCsvRequest};
use App\Http\Resources\ProductTableResource;
use App\Models\{Product, Supplier, Warehouse};
use Illuminate\Http\{JsonResponse, Request};

class KitController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Product::class);
    }

    public function index($keyword='')
    {
        $customers = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $data = new ProductsDataTableDto(
            Supplier::whereIn('customer_id', $customers)->get(),
            Warehouse::whereIn('customer_id', $customers)->get(),
        );

        return view('products.kits', [
            'page' => 'kits',
            'keyword' => $keyword,
            'data' => $data,
            'datatableOrder' => app('editColumn')->getDatatableOrder('products'),
        ]);
    }

    public function dataTable(Request $request): JsonResponse
    {
        $kitsCollection = app('product')->getKitsQuery($request);

        $kits = $kitsCollection->get()->unique('id');
        $kitsCollection = ProductTableResource::collection($kits);
        $visibleFields = app('editColumn')->getVisibleFields('kits');

        return response()->json([
            'draw' => (int)$request->get('draw'),
            'data' => $kitsCollection,
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    /**
     * @param ImportCsvRequest $request
     * @return JsonResponse
     */
    public function importCsv(ImportCsvRequest $request): JsonResponse
    {
        $message = app('product')->importKitsCsv($request);

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * @param ExportCsvRequest $request
     * @return mixed
     */
    public function exportCsv(ExportCsvRequest $request)
    {
        return app('product')->exportKitsCsv($request);
    }
}
