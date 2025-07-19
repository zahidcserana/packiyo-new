<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Webhook\StoreBatchRequest;
use App\Http\Requests\Webhook\UpdateBatchRequest;
use App\Http\Requests\Webhook\DestroyBatchRequest;
use App\Http\Resources\WebhookResource;
use App\Http\Resources\WebhookCollection;
use App\JsonApi\V1\Webhooks\WebhookSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Models\Webhook;
use App\Models\UserRole;
use App\Http\Controllers\ApiController;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\FetchOne;
use LaravelJsonApi\Laravel\Http\Requests\AnonymousCollectionQuery;

/**
 * Class WebhookController
 * @package App\Http\Controllers\Api
 * @group Webhooks
 */
class WebhookController extends ApiController
{
    use FetchOne;

    public function __construct()
    {
        $this->authorizeResource(Webhook::class);
    }

    /**
     * @param WebhookSchema $schema
     * @param AnonymousCollectionQuery $request
     * @return DataResponse
     */
    public function index(WebhookSchema $schema, AnonymousCollectionQuery $request): DataResponse
    {
        $models = $schema
            ->repository()
            ->queryAll()
            ->withRequest($request)
            ->query();

        $user = auth()->user();

        if ($user->user_role_id != UserRole::ROLE_ADMINISTRATOR) {
            $models = $models->where('user_id', auth()->user()->id);
        }

        if ($request->page()) {
            $models = $models->paginate($request->page());
        } else {
            $models = $models->get();
        }

        return new DataResponse($models);
    }

    /**
     * @param WebhookSchema $schema
     * @param StoreBatchRequest $request
     * @param AnonymousCollectionQuery $collectionQuery
     * @return DataResponse
     */
    public function store(WebhookSchema $schema, StoreBatchRequest $request, AnonymousCollectionQuery $collectionQuery): DataResponse
    {
        $storedIds = (app()->webhook->storeBatch($request))->pluck('id');

        $models = $schema
            ->repository()
            ->queryAll()
            ->withRequest($collectionQuery)
            ->query();

        $models = $models->whereIn('id', $storedIds);

        if ($collectionQuery->page()) {
            $models = $models->paginate($collectionQuery->page());
        } else {
            $models = $models->get();
        }

        return new DataResponse($models);
    }

    /**
     * @param WebhookSchema $schema
     * @param UpdateBatchRequest $request
     * @param AnonymousCollectionQuery $collectionQuery
     * @return DataResponse
     */
    public function update(WebhookSchema $schema, UpdateBatchRequest $request, AnonymousCollectionQuery $collectionQuery): DataResponse
    {
        $updatedIds = (app()->webhook->updateBatch($request))->pluck('id');

        $models = $schema
            ->repository()
            ->queryAll()
            ->withRequest($collectionQuery)
            ->query();

        $models = $models->whereIn('id', $updatedIds);

        if ($collectionQuery->page()) {
            $models = $models->paginate($collectionQuery->page());
        } else {
            $models = $models->get();
        }

        return new DataResponse($models);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  DestroyBatchRequest $request
     * @return JsonResponse
     */
    public function destroy(DestroyBatchRequest $request): JsonResponse
    {
        return response()->json(
            new ResourceCollection(
                app()->webhook->destroyBatch($request)
            )
        );
    }
}
