<?php

namespace App\Http\Controllers\Api\FrontendV1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Image\StoreRequest as ImageStoreRequest;
use App\JsonApi\FrontendV1\Customers\CustomerQuery;
use App\JsonApi\FrontendV1\Customers\CustomerRequest;
use App\Models\Customer;
use Illuminate\Contracts\Support\Responsable;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class CustomerController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    /**
     * @param CustomerRequest $request
     * @param CustomerQuery $query
     * @return Responsable
     */
    protected function creating(CustomerRequest $request, CustomerQuery $query): Responsable
    {
        $customer = app('customer')->store($request);

        if ($customer) {
            return DataResponse::make($customer)
                ->withQueryParameters($query)
                ->didCreate();
        }

        return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param Customer $customer
     * @param CustomerRequest $request
     * @param CustomerQuery $query
     * @return Responsable
     */
    protected function updating(Customer $customer, CustomerRequest $request, CustomerQuery $query): Responsable
    {
        $customer = app('customer')->update($request, $customer);

        if ($customer) {
            return DataResponse::make($customer)
                ->withQueryParameters($query)
                ->didCreate();
        }

        return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function uploadImage(Customer $customer, ImageStoreRequest $request)
    {
        if (!empty($request->file('file'))) {
            app('customer')->saveImage($customer, $request->file('file'), $request->get('image_type'));
        }
    }
}
