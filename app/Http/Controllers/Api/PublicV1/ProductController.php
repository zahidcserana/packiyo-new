<?php

namespace App\Http\Controllers\Api\PublicV1;

use App\Http\Controllers\Controller;
use App\JsonApi\PublicV1\Products\ProductQuery;
use App\JsonApi\PublicV1\Products\ProductRequest;
use App\Models\Product;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use App\Http\Requests\Product\AddToLocationRequest;

class ProductController extends Controller
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
     * @param ProductRequest $request
     * @param ProductQuery $query
     * @return Responsable
     */
    protected function creating(ProductRequest $request, ProductQuery $query): Responsable
    {
        $product = app('product')->store($request);

        if ($product) {
            return DataResponse::make($product)
                ->withQueryParameters($query)
                ->didCreate();
        }

        return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param Product $product
     * @param ProductRequest $request
     * @param ProductQuery $query
     * @return Responsable
     */
    protected function updating(Product $product, ProductRequest $request, ProductQuery $query): Responsable
    {
        $product = app('product')->update($request, $product);

        if ($product) {
            return DataResponse::make($product)
                ->withQueryParameters($query);
        }

        return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param Product $product
     * @param ProductRequest $request
     * @return Response
     */
    protected function deleting(Product $product, ProductRequest $request): Response
    {
        if (app('product')->destroy(null, $product)) {
            return response(null, Response::HTTP_NO_CONTENT);
        } else {
            return response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function inventory(Product $product, AddToLocationRequest $request): Responsable
    {
        $this->authorize('update', $product);

        app('product')->addToLocation($request, $product);

        return new DataResponse($product);
    }
}
