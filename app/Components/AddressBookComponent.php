<?php

namespace App\Components;

use App\Http\Requests\AddressBook\DestroyBatchRequest;
use App\Http\Requests\AddressBook\DestroyRequest;
use App\Http\Requests\AddressBook\StoreBatchRequest;
use App\Http\Requests\AddressBook\StoreRequest;
use App\Http\Requests\AddressBook\UpdateBatchRequest;
use App\Http\Requests\AddressBook\UpdateRequest;
use App\Http\Resources\AddressBookCollection;
use App\Http\Resources\AddressBookResource;
use App\Models\AddressBook;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AddressBookComponent extends BaseComponent
{
    public function store(StoreRequest $request, $fireWebhook = true)
    {
        $input = $request->validated();
        $contactInformationData = Arr::get($input, 'contact_information');

        $addressBook = AddressBook::create($input);
        $this->createContactInformation($contactInformationData, $addressBook);

        if ($fireWebhook) {
            $this->webhook(new AddressBookResource($addressBook), AddressBook::class, Webhook::OPERATION_TYPE_STORE, $addressBook->customer_id);
        }

        return $addressBook;
    }

    public function storeBatch(StoreBatchRequest $request): Collection
    {
        $responseCollection = new Collection();
        $input = $request->validated();

        foreach ($input as $record) {
            $storeRequest = StoreRequest::make($record);
            $responseCollection->add($this->store($storeRequest, false));
        }

        $this->batchWebhook($responseCollection, AddressBook::class, AddressBookCollection::class, Webhook::OPERATION_TYPE_STORE);

        return $responseCollection;
    }

    public function update(UpdateRequest $request, AddressBook $addressBook, $fireWebhook = true): AddressBook
    {
        $input = $request->validated();

        $addressBook->contactInformation->update(Arr::get($input, 'contact_information'));
        $addressBook->update($input);

        if ($fireWebhook) {
            $this->webhook(new AddressBookResource($addressBook), AddressBook::class, Webhook::OPERATION_TYPE_UPDATE, $addressBook->customer_id);
        }

        return $addressBook;
    }

    public function updateBatch(UpdateBatchRequest $request): Collection
    {
        $responseCollection = new Collection();
        $input = $request->validated();

        foreach ($input as $record) {
            $updateRequest = UpdateRequest::make($record);
            $addressBook = AddressBook::find($record['id']);

            $responseCollection->add($this->update($updateRequest, $addressBook, false));
        }

        $this->batchWebhook($responseCollection, AddressBook::class, AddressBookCollection::class, Webhook::OPERATION_TYPE_UPDATE);

        return $responseCollection;
    }

    public function destroy(DestroyRequest $request, AddressBook $addressBook, $fireWebhook = true)
    {
        $addressBook->delete();

        $response = ['id' => $addressBook->id, 'customer_id' => $addressBook->customer_id];

        if ($fireWebhook == true) {
            $this->webhook($response, AddressBook::class, Webhook::OPERATION_TYPE_DESTROY, $addressBook->customer_id);
        }

        return $response;
    }

    public function destroyBatch(DestroyBatchRequest $request)
    {
        $responseCollection = new Collection();
        $input = $request->validated();

        foreach ($input as $record) {
            $destroyRequest = DestroyRequest::make($record);
            $addressBook = AddressBook::find($record['id']);

            $responseCollection->add($this->destroy($destroyRequest, $addressBook, false));
        }

        $this->batchWebhook($responseCollection, AddressBook::class, AddressBookCollection::class, Webhook::OPERATION_TYPE_DESTROY);

        return $responseCollection;
    }

    /**
     * @param $filterInputs
     * @param mixed $sortColumnName
     * @param mixed $sortDirection
     * @return Builder
     */
    public function getQuery($filterInputs, string $sortColumnName = 'address_books.id', string $sortDirection = 'desc'): Builder
    {
        $customerIds = app('user')->getSelectedCustomers()->pluck('id')->toArray();

        $addressBookCollection = AddressBook::join('customers', 'address_books.customer_id', '=', 'customers.id')
            ->join('contact_informations', 'address_books.id', '=', 'contact_informations.object_id')
            ->leftJoin('countries', 'contact_informations.country_id', '=', 'countries.id')
            ->where('contact_informations.object_type', AddressBook::class)
            ->whereIn('address_books.customer_id', $customerIds)
            ->where(function ($query) use ($filterInputs) {
                if (Arr::get($filterInputs, 'country')) {
                    $query->where('contact_informations.country_id', $filterInputs['country']);
                }
            })
            ->select('address_books.*')
            ->groupBy('address_books.id')
            ->orderBy($sortColumnName, $sortDirection);

        return $addressBookCollection;
    }

    /**
     * @param string $term
     * @param $addressBookCollection
     * @return mixed
     */
    public function searchQuery(string $term, $addressBookCollection): mixed
    {
        $term = $term . '%';

        return $addressBookCollection->where(function ($q) use ($term) {
            $q->where('address_books.name', 'like', $term)
                ->orWhereHas('contactInformation', function ($query) use ($term) {
                    $query->where('name', 'like', $term)
                        ->orWhere('address', 'like', $term)
                        ->orWhere('city', 'like', $term)
                        ->orWhere('zip', 'like', $term);
                })
                ->orWhereHas('customer.contactInformation', function ($query) use ($term) {
                    $query->where('name', 'like', $term);
                });
        });
    }
}
