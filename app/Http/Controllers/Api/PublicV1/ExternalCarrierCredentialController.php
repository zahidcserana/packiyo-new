<?php

namespace App\Http\Controllers\Api\PublicV1;

use App\Http\Controllers\Controller;
use App\JsonApi\PublicV1\ExternalCarrierCredentials\ExternalCarrierCredentialQuery;
use App\JsonApi\PublicV1\ExternalCarrierCredentials\ExternalCarrierCredentialRequest;
use App\Models\ExternalCarrierCredential;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class ExternalCarrierCredentialController extends Controller
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

    protected function creating(ExternalCarrierCredentialRequest $request, ExternalCarrierCredentialQuery $query): Responsable
    {
        $externalCarrierCredential = app('externalCarrierCredential')->store($request);

        if ($externalCarrierCredential) {
            return DataResponse::make($externalCarrierCredential)
                ->withQueryParameters($query)
                ->didCreate();
        }

        return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function updating(ExternalCarrierCredential $externalCarrierCredential,
                                ExternalCarrierCredentialRequest $request,
                                ExternalCarrierCredentialQuery $query): Responsable
    {
        $externalCarrierCredential = app('externalCarrierCredential')->update($request, $externalCarrierCredential);

        if ($externalCarrierCredential) {
            return DataResponse::make($externalCarrierCredential)
                ->withQueryParameters($query);
        }

        return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function deleting(ExternalCarrierCredential $externalCarrierCredential, ExternalCarrierCredentialRequest $request): Response
    {
        if (app('externalCarrierCredential')->destroy(null, $externalCarrierCredential)) {
            return response(null, Response::HTTP_NO_CONTENT);
        } else {
            return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
