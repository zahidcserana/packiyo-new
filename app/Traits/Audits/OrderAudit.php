<?php

namespace App\Traits\Audits;

use App\Models\Order;
use App\Models\Warehouse;
use Illuminate\Support\Arr;
use App\Models\OrderStatus;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;

trait OrderAudit
{
    use AuditTrait;

    public static array $columnTitle = [
        'ship_before' => 'Required shipping date',
        'hold_until' => 'Hold until',
        'fulfilled_at' => 'Fulfilled date',
        'scheduled_delivery' => 'Scheduled delivery'
    ];

    /**
     * @param array $data
     * @return array $data
     */
    public function transformAudit(array $data): array
    {
        $data['custom_message'] = '';

        if (Arr::has($data, 'new_values.order_status_id')) {
            $data['old_values']['order_status'] = OrderStatus::find($this->getOriginal('order_status_id'))->name ?? '';
            $data['new_values']['order_status'] = OrderStatus::find($this->getAttribute('order_status_id'))->name ?? __('Pending');

            Arr::forget($data, 'old_values.order_status_id');
            Arr::forget($data, 'new_values.order_status_id');
        }

        if (Arr::has($data, 'new_values.shipping_method_id')) {
            $data['old_values']['shipping_method'] = ShippingMethod::find($this->getOriginal('shipping_method_id'))->carrierNameAndName ?? '';
            $data['new_values']['shipping_method'] = ShippingMethod::find($this->getAttribute('shipping_method_id'))->carrierNameAndName ?? '';

            Arr::forget($data, 'old_values.shipping_method_id');
            Arr::forget($data, 'new_values.shipping_method_id');
        }

        if ($this->auditEvent == 'created') {
            $data['custom_message'] = __('Order was placed');
            $data['old_values'] = null;
            $data['new_values'] = ['message' => $data['custom_message']];
        } elseif ($this->auditEvent == 'updated') {
            foreach (Arr::get($data, 'new_values') as $attribute => $value) {
                if ($attribute == 'message') {
                    $data['custom_message'] = Arr::get($data, 'new_values.message', '');
                } elseif (in_array($attribute, Order::$columnBoolean)) {
                    if (in_array($attribute, ['allow_partial', 'ready_to_ship', 'ready_to_pick'])) {
                        $condition = $this->getAttribute($attribute) ? __('Enabled') : __('Disabled');
                    } else {
                        $condition = $this->getAttribute($attribute) ? __('Added') : __('Removed');
                    }

                    $data['custom_message'] .= __(':condition :attribute <br/>', [
                        'attribute' => str_replace('_', ' ', $attribute),
                        'condition' => $condition,
                    ]);
                } elseif ($attribute == 'shipping_priority_score') {
                    $data['custom_message'] .= __('Priority updated to <em>":new"</em> <br/>', [
                        'attribute' => str_replace('_', ' ', ucfirst($attribute)),
                        'new' => $this->getAttribute($attribute)
                    ]);
                } elseif ($attribute == 'tags') {
                    $oldTag = Arr::pluck(Arr::get($data, 'old_values.tags'), 'name');
                    $newTag = Arr::pluck(Arr::get($data, 'new_values.tags'), 'name');

                    $addedTag = array_values(array_diff($newTag, $oldTag));
                    $removedTag = array_values(array_diff($oldTag, $newTag));

                    if (!empty($removedTag)) {
                        $data['custom_message'] = __('Removed <em>":tag"</em> :attribute', ['tag' => implode(', ', $removedTag), 'attribute' => count($removedTag) > 1 ? 'tags' : 'tag']);
                    }

                    if (!empty($addedTag)) {
                        $data['custom_message'] = __('Added <em>":tag"</em> :attribute', ['tag' => implode(', ', $addedTag), 'attribute' => count($addedTag) > 1 ? 'tags' : 'tag']);
                    }
                } elseif ($attribute == 'warehouse_id') {
                    $fromWarehouseId = Arr::get($data, 'old_values.warehouse_id');
                    $toWarehouseId = Arr::get($data, 'new_values.warehouse_id');

                    $fromWarehouse = Warehouse::find($fromWarehouseId)->contactInformation->name ?? __('None');
                    $toWarehouse = Warehouse::find($toWarehouseId)->contactInformation->name;

                    $data['custom_message'] = __('Warehouse changed from <em>:from</em> to <em>:to</em><br/>', ['from' => $fromWarehouse, 'to' => $toWarehouse]);
                } else {
                    $field = Arr::get(static::$columnTitle, $attribute, str_replace('_', ' ', ucfirst($attribute)));

                    $data['custom_message'] .= static::setAuditMessage($field, $data, $attribute) . ' <br/>';
                }
            }
        } elseif (in_array($this->auditEvent, ['cancelled', 'uncancelled', 'fulfilled', 'unfulfilled', 'shipped', 'return', 'reshipped'])) {
            if ($this->auditEvent == 'cancelled' && request()->is('api/*')) {
                $data['new_values']['message'] = __('Order canceled on Store');
            }

            $data['custom_message'] = Arr::get($data, 'new_values.message', '');
        } else {
            $data['custom_message'] = Arr::get($this->auditCustomNew, 'message', '');
        }

        return $data;
    }

    public static function auditShipment(Shipment $shipment)
    {
        $shipmentTrackings = '';

        if (!is_null($shipment->shipmentTrackings)) {
            foreach ($shipment->shipmentTrackings as $tracking) {
                $shipmentTrackings .= $tracking->tracking_url . ', ' . $tracking->tracking_number;
            }
        }

        $message = __('Order was shipped using :shippingMethod :shipmentTrackings', [
            'shippingMethod' => !is_null($shipment->shippingMethod) ? $shipment->shippingMethod->carrierNameAndName : 'Generic',
            'shipmentTrackings' => $shipmentTrackings != '' ? ' - ' . $shipmentTrackings : ''
        ]);

        static::auditCustomEvent($shipment->order, 'shipped', $message);
    }

    public static function getAudits(Request $request, Order $order)
    {
        $order = $order->load(
            'audits.user.contactInformation',
            'shippingContactInformation.audits.user.contactInformation',
            'billingContactInformation.audits.user.contactInformation',
            'orderItems.audits.user.contactInformation'
        );

        $audits = collect([
            $order->shippingContactInformation->audits,
            $order->billingContactInformation->audits
        ])->reduce(function ($collection, $item) {
            if (empty($item) || $item->isEmpty()) {
                return $collection;
            }
            return $collection->merge($item);
        }, $order->audits);

        $order->orderItems->map(function ($orderItem, $key) use ($audits) {
            $orderItem->audits->map(function ($audit, $key) use ($audits) {
                $audits->push($audit);
            });

            $orderItem->toteOrderItems->map(function ($toteOrderItem) use ($audits) {
                $toteOrderItem->audits->map(function ($audit) use ($audits) {
                    $audits->push($audit);
                });
            });
        });

        return app('audit')->prepareEachAudits($request, $audits);
    }
}
