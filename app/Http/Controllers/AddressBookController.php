<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressBook\DestroyRequest;
use App\Http\Requests\AddressBook\StoreRequest;
use App\Http\Requests\AddressBook\UpdateRequest;
use App\Http\Resources\AddressBookTableResource;
use App\Models\AddressBook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class AddressBookController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(AddressBook::class);
    }

    public function index()
    {
        return view('address_books.index', [
            'datatableOrder' => app()->editColumn->getDatatableOrder('address-books'),
        ]);
    }

    public function dataTable(Request $request): JsonResponse
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'address_books.id';
        $sortDirection = 'desc';
        $filterInputs = $request->get('filter_form');
        $term = $request->get('search')['value'];

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $addressBookCollection = app('addressBook')->getQuery($filterInputs, $sortColumnName, $sortDirection);

        if ($term) {
            $addressBookCollection = app('addressBook')->searchQuery($term, $addressBookCollection);
        }

        $start = $request->get('start');
        $length = $request->get('length');

        if ($length == -1) {
            $length = 10;
        }

        if ($length) {
            $addressBookCollection = $addressBookCollection->skip($start)->limit($length);
        }

        $addressBooks = $addressBookCollection->get();
        $visibleFields = app('editColumn')->getVisibleFields('address-books');

        $addressBookCollection = AddressBookTableResource::collection($addressBooks);

        return response()->json([
            'data' => $addressBookCollection,
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX
        ]);
    }

    public function modal(AddressBook $addressBook = null): \Illuminate\Contracts\View\View
    {
        return View::make('shared.modals.components.addressBook', compact('addressBook'));
    }

    public function store(StoreRequest $request): JsonResponse
    {
        app('addressBook')->store($request);

        return response()->json([
            'success' => true,
            'message' => __('Address successfully added.')
        ]);
    }

    public function update(UpdateRequest $request, AddressBook $addressBook): JsonResponse
    {
        app('addressBook')->update($request, $addressBook);

        return response()->json([
            'success' => true,
            'message' => __('Address successfully updated.')
        ]);
    }

    public function destroy(DestroyRequest $request, AddressBook $addressBook)
    {
        app('addressBook')->destroy($request, $addressBook);

        return redirect()->back()->withStatus('Address successfully deleted.');
    }
}
