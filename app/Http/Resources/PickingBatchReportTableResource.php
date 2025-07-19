<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PickingBatchReportTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        unset($resource);

        $orderIds = [];

        foreach ($this->pickingBatchItemsWithTrashed as $pickingBatchItem) {
            $orderIds[] = $pickingBatchItem->orderItem->order->id;
        }

        $lastUpdated = $this->pickingBatchItemsWithTrashed->sortByDesc('updated_at')->first();
        $user = $this->tasks->first()->user ?? null;

        $resource['picking_batch_id'] = $this->id;
        $resource['tag_name'] = $this->tag_name;
        $resource['exclude_single_line_orders'] = $this->exclude_single_line_orders ? __('YES') : __('NO');
        $resource['is_active'] = empty($this->pickingBatchItemsNotFinished->first()) ? 0 : 1;
        $resource['start_date_time'] = user_date_time($this->created_at, true);
        $resource['last_action_date_time'] = user_date_time($lastUpdated ? $lastUpdated->updated_at : $this->updated_at, true);
        $resource['total_products_in_batch'] = $this->pickingBatchItemsWithTrashed->sum('quantity');
        $resource['total_picked_products'] = $this->pickingBatchItemsWithTrashed->sum('quantity_picked');
        $resource['amount_of_orders'] = count(array_count_values($orderIds));
        $resource['user'] = $user->contactInformation->name ?? '';
        $resource['link_items'] = route('picking_batch.getItems', ['pickingBatch' => $this->id ]);
        $resource['link_clear_batch'] = ['token' => csrf_token(), 'url' => route('picking_batch.clearBatch', ['pickingBatch' => $this, 'id' => $this->id])];
        $resource['is_deleted'] = (int)isset($this->deleted_at);
        $resource['batch_time'] = $this->getTotalBatchTime() ?? '';
        $resource['time_per_pick'] = $this->getTimePerPick() ?? '';

        return $resource;
    }
}

