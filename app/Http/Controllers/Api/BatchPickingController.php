<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PickingBatch\ClosePickingTaskRequest;
use App\Http\Requests\PickingBatch\ExistingItemRequest;
use App\Http\Requests\PickingBatch\MultiOrderRequest;
use App\Http\Requests\PickingBatch\PickRequest;
use App\Http\Requests\PickingBatch\SingleItemBatchRequest;
use App\Http\Requests\PickingBatch\SingleOrderRequest;
use App\JsonApi\V1\PickingBatches\PickingBatchSchema;
use App\Models\Customer;
use App\Models\PickingBatch;
use App\Http\Controllers\ApiController;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Requests\AnonymousCollectionQuery;

/**
 * Class BatchPickingController
 * @package App\Http\Controllers\Api
 * @group Batch Picking
 */
class BatchPickingController extends ApiController
{
    public function singleItemBatchPicking(PickingBatchSchema $schema, AnonymousCollectionQuery $collectionQuery, SingleItemBatchRequest $request): DataResponse
    {
        $customer = Customer::find($request->get('customer_id'));
        $customerWarehouseId = app('user')->getCustomerWarehouseId($customer);

        $pickingBatch = app('routeOptimizer')->findOrCreatePickingBatch(
            $request->get('customer_id'),
            $request->get('quantity'),
            PickingBatch::TYPE_SIB,
            $request->get('tag_id'),
            $request->get('tag_name'),
            $request->get('order_id'),
            $customerWarehouseId
        );

        $status = $pickingBatch ? ($pickingBatch->status ?? null) : null;

        return DataResponse::make($status ? null : $pickingBatch)->withMeta(['status' => $pickingBatch->status ?? '']);
    }

    public function multiOrderPicking(PickingBatchSchema $schema, AnonymousCollectionQuery $collectionQuery, MultiOrderRequest $request): DataResponse
    {
        $customer = Customer::find($request->get('customer_id'));
        $customerWarehouseId = app('user')->getCustomerWarehouseId($customer);

        $pickingBatch = app('routeOptimizer')->findOrCreatePickingBatch(
            $request->get('customer_id'),
            $request->get('quantity'),
            PickingBatch::TYPE_MIB,
            $request->get('tag_id'),
            $request->get('tag_name'),
            $request->get('order_id'),
            $customerWarehouseId
        );

        $status = $pickingBatch ? ($pickingBatch->status ?? null) : null;

        return DataResponse::make($status ? null : $pickingBatch)->withMeta(['status' => $pickingBatch->status ?? '']);
    }

    public function singleOrderPicking(PickingBatchSchema $schema, AnonymousCollectionQuery $collectionQuery, SingleOrderRequest $request): DataResponse
    {
        $customer = Customer::find($request->get('customer_id'));
        $customerWarehouseId = app('user')->getCustomerWarehouseId($customer);

        $pickingBatch = app('routeOptimizer')->findOrCreatePickingBatch(
            $request->get('customer_id'),
            1,
            PickingBatch::TYPE_SO,
            $request->get('tag_id'),
            $request->get('tag_name'),
            $request->get('order_id'),
            $customerWarehouseId
        );

        $status = $pickingBatch ? ($pickingBatch->status ?? null) : null;

        return DataResponse::make($status ? null : $pickingBatch)->withMeta(['status' => $pickingBatch->status ?? '']);
    }

    public function existingItems(PickingBatchSchema $schema, AnonymousCollectionQuery $collectionQuery, ExistingItemRequest $request): DataResponse
    {
        $user = auth()->user();

        $pickingBatch = app('routeOptimizer')->getExistingPickingBatch($user->id, $request->get('type'), $request->get('order_id'), $request->get('customer_id'));

        $status = $pickingBatch ? ($pickingBatch->status ?? null) : null;

        return DataResponse::make($status ? null : $pickingBatch)->withMeta(['status' => $pickingBatch->status ?? '', 'pickingBatchId' => $pickingBatch->id ?? null]);
    }

    public function closePickingTask(PickingBatchSchema $schema, AnonymousCollectionQuery $collectionQuery, ClosePickingTaskRequest $request): DataResponse
    {
        $pickingBatch = PickingBatch::find($request->picking_batch_id);

        $task = app('pickingBatch')->closePickingTask($pickingBatch);

        return new DataResponse($task);
    }

    /**
     * @param PickingBatchSchema $schema
     * @param AnonymousCollectionQuery $collectionQuery
     * @param PickRequest $request
     * @return DataResponse
     */
    public function pick(PickingBatchSchema $schema, AnonymousCollectionQuery $collectionQuery, PickRequest $request): DataResponse
    {
        return new DataResponse(app('routeOptimizer')->pick($request));
    }
}
