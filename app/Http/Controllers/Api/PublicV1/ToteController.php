<?php

namespace App\Http\Controllers\Api\PublicV1;

use App\Http\Requests\Tote\PickOrderItemsRequest;
use App\JsonApi\PublicV1\Totes\ToteQuery;
use App\JsonApi\PublicV1\Totes\ToteRequest;
use App\Models\Location;
use App\Models\Tote;
use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Controllers\ApiController;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Illuminate\Contracts\Support\Responsable;
use LaravelJsonApi\Laravel\Http\Requests\AnonymousQuery;

/**
 * Class ToteController
 * @package App\Http\Controllers\Api
 * @group Totes
 */
class ToteController extends ApiController
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
     * @param ToteRequest $request
     * @param ToteQuery $query
     * @return Responsable
     */
    protected function creating(ToteRequest $request, ToteQuery $query): Responsable
    {
        $tote = app('tote')->store($request);

        if ($tote) {
            return DataResponse::make($tote)
                ->withQueryParameters($query)
                ->didCreate();
        }

        return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param Tote $tote
     * @param ToteRequest $request
     * @param ToteQuery $query
     * @return Responsable
     */
    protected function updating(Tote $tote, ToteRequest $request, ToteQuery $query): Responsable
    {
        $tote = app('tote')->update($request, $tote);

        if ($tote) {
            return DataResponse::make($tote)
                ->withQueryParameters($query);
        }

        return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param Tote $tote
     * @param PickOrderItemsRequest $request
     * @param AnonymousQuery $query
     * @return DataResponse
     * @throws AuthorizationException
     */
    public function pickOrderItems(Tote $tote, PickOrderItemsRequest $request, AnonymousQuery $query): DataResponse
    {
        $this->authorize('view', $tote);

        $location = Location::findOrFail($request['location_id'])->first();

        $toteItem = app('tote')->pickOrderItems($request, $tote, $location);

        return DataResponse::make($toteItem)
            ->withQueryParameters($query);
    }

    /**
     * @param Tote $tote
     * @return DataResponse
     * @throws AuthorizationException
     */
    public function emptyTote(Tote $tote): DataResponse
    {
        $this->authorize('view', $tote);

        app('tote')->clearTote($tote);

        return new DataResponse($tote);
    }
}
