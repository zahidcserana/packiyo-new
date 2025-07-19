<?php

namespace App\Http\Controllers\Api\PublicV1;

use App\Components\LinkComponent;
use App\Http\Controllers\Controller;
use App\JsonApi\PublicV1\Links\LinkRequest;
use App\Models\Link;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Requests\AnonymousQuery;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class LinkController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;

    public function __construct
    (
        private readonly LinkComponent $linkComponent)
    {
        $this->authorizeResource(Link::class);
    }

    public function store(LinkRequest $request, AnonymousQuery $query): Responsable|Response
    {
        try {
            $link = $this->linkComponent->store($request->validated());

            if ($link) {
                return DataResponse::make($link)
                    ->withQueryParameters($query)
                    ->didCreate();
            }
        } catch (\Exception $exception) {
            Log::error('Enable to store the Link', [$exception->getMessage()]);
            throw $exception;
        }
        return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
