<?php

namespace App\Http\Controllers\Api\PublicV1;

use App\Http\Controllers\Controller;
use App\JsonApi\PublicV1\Webhooks\WebhookRequest;
use App\Models\Webhook;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use LaravelJsonApi\Laravel\Http\Requests\AnonymousQuery;

class WebhookController extends Controller
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
     * @param WebhookRequest $request
     * @param AnonymousQuery $query
     * @return Responsable
     */
    protected function creating(WebhookRequest $request, AnonymousQuery $query): Responsable
    {
        $webhook = app('webhook')->store($request);

        if ($webhook) {
            return DataResponse::make($webhook)
                ->withQueryParameters($query)
                ->didCreate();
        }

        return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param Webhook $webhook
     * @param WebhookRequest $request
     * @param AnonymousQuery $query
     * @return Responsable
     */
    protected function updating(Webhook $webhook, WebhookRequest $request, AnonymousQuery $query): Responsable
    {
        $webhook = app('webhook')->update($request, $webhook);

        if ($webhook) {
            return DataResponse::make($webhook)
                ->withQueryParameters($query);
        }

        return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param Webhook $webhook
     * @param WebhookRequest $request
     * @return Response
     */
    protected function deleting(Webhook $webhook, WebhookRequest $request): Response
    {
        if (app('webhook')->destroy(null, $webhook)) {
            return response(null, Response::HTTP_NO_CONTENT);
        } else {
            return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
