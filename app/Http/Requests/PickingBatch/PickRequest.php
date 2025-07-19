<?php

namespace App\Http\Requests\PickingBatch;

use App\Http\Requests\FormRequest;
use App\Models\{Location, PickingBatch, PickingBatchItem, Task, Tote, Warehouse};
use App\Rules\BelongsToWarehouse;
use Illuminate\Validation\Rule;

class PickRequest extends FormRequest
{
    public static function validationRules(): array
    {
        $type = static::getInputField('type');
        [$order] = static::getInputField('orders');
        $toteId = static::getInputField('tote_id');
        $pickingBatchId = static::getInputField('picking_batch_id');

        $pickingBatch = PickingBatch::find($pickingBatchId);
        $warehouse = Warehouse::find($pickingBatch->warehouse_id);

        $tote = Tote::find($toteId);

        if (is_null($warehouse)) {
            $warehouse = Warehouse::find($tote->warehouse_id);
        }

        switch ($type) {
            case 'sib':
                if ($toteOrderItem = $tote->placedToteOrderItems->first()) {
                    $pickingBatchItem = PickingBatchItem::find($toteOrderItem->picking_batch_item_id);

                    if ($pickingBatchItem->picking_batch_id !== $pickingBatchId) {
                        $toteId = '';
                    }
                }
                break;
            default:
                if ($toteOrderItem = $tote->placedToteOrderItems->first()) {
                    if ($toteOrderItem->orderItem->order_id !== $order['id']) {
                        $toteId = '';
                    }
                }
        }

        $rules = [
            'picking_batch_id' => [
                'required'
            ],
            'product_id' => [
                'required',
            ],
            'tote_id' => [
                'required',
                Rule::in([$toteId]),
                new BelongsToWarehouse($warehouse, Tote::class)
            ],
            'location_id' => [
                'required',
                new BelongsToWarehouse($warehouse, Location::class),
                Rule::exists('locations', 'id')->where(function ($query) {
                    return $query
                        ->where('pickable_effective', 1)
                        ->where('disabled_on_picking_app_effective', 0);
                })
            ],
            'orders' => [
                'required'
            ],
            'type' => [
                'required'
            ],
        ];

        if (!Task::where('taskable_id', $pickingBatchId)->where('taskable_type', PickingBatch::class)->where('completed_at', null)->count()) {
            $rules = array_merge($rules, ['picking_batch_completed' => [
                'required',
                'picking_batch_completed'
            ]]);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'picking_batch_completed' => 'Picking batch is already completed.',
            'location_id.exists' => 'This location is not pickable.',
        ];
    }
}
