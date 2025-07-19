<?php

namespace App\Http\Controllers\Api\PublicV1;

use App\Enums\Source;
use App\Http\Controllers\Controller;
use App\JsonApi\PublicV1\Orders\OrderQuery;
use App\JsonApi\PublicV1\Orders\OrderRequest;
use App\Models\Order;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class OrderController extends Controller
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
     * @param OrderRequest $request
     * @param OrderQuery $query
     * @return Responsable
     */
    protected function creating(OrderRequest $request, OrderQuery $query): Responsable
    {
        $order = app('order')->store($request, source: Source::PUBLIC_API);

        if ($order) {
            return DataResponse::make($order)
                ->withQueryParameters($query)
                ->didCreate();
        }

        return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param Order $order
     * @param OrderRequest $request
     * @param OrderQuery $query
     * @return Responsable
     */
    protected function updating(Order $order, OrderRequest $request, OrderQuery $query): Responsable
    {
        $order = app('order')->update($request, $order,  source: Source::PUBLIC_API);

        if ($order) {
            return DataResponse::make($order)
                ->withQueryParameters($query);
        }

        return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param Order $order
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function cancel(Order $order): Response
    {
        $this->authorize('cancel', $order);

        app('order')->cancelOrder($order);

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
