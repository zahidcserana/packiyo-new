<?php

namespace App\Http\Controllers\Api\PublicV1;

use App\Http\Controllers\Controller;
use App\JsonApi\PublicV1\PurchaseOrders\PurchaseOrderQuery;
use App\JsonApi\PublicV1\PurchaseOrders\PurchaseOrderRequest;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class PurchaseOrderController extends Controller
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
     * @param PurchaseOrderRequest $request
     * @param PurchaseOrderQuery $query
     * @return Responsable
     */
    protected function creating(PurchaseOrderRequest $request, PurchaseOrderQuery $query): Responsable
    {
        $purchaseOrder = app('purchaseOrder')->store($request);

        if ($purchaseOrder) {
            return DataResponse::make($purchaseOrder)
                ->withQueryParameters($query)
                ->didCreate();
        }

        return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    protected function updating(PurchaseOrder $purchaseOrder, PurchaseOrderRequest $request, PurchaseOrderQuery $query): Responsable
    {
        $purchaseOrder = app('purchaseOrder')->update($request, $purchaseOrder);

        if ($purchaseOrder) {
            return DataResponse::make($purchaseOrder)
                ->withQueryParameters($query);
        }

        return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
