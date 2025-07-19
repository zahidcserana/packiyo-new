<?php

namespace App\Http\Controllers\Api\FrontendV1;

use App\Http\Controllers\Controller;
use App\JsonApi\FrontendV1\Images\ImageRequest;
use App\Models\Image;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Illuminate\Http\Response;

class ImageController extends Controller
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
     * @param Image $image
     * @param ImageRequest $request
     * @return Response
     */
    protected function deleting(Image $image, ImageRequest $request): Response
    {
        if (app('home')->deleteImage($request, $image)) {
            return response(null, Response::HTTP_NO_CONTENT);
        } else {
            return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
