<?php

namespace App\Traits\Audits;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderStatus;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

trait PurchaseOrderAudit
{
    use AuditTrait;

    public static $columnTitle = [
        'ordered_at' => 'Ordered date',
        'expected_at' => 'Expected date'
    ];

    /**
     * @param array $data
     * @return array $data
     */
    public function transformAudit(array $data): array
    {
        $data['custom_message'] = '';

        if (Arr::has($data, 'new_values.purchase_order_status_id')) {
            $data['old_values']['po_status'] = PurchaseOrderStatus::find($this->getOriginal('purchase_order_status_id'))->name ?? '';
            $data['new_values']['po_status'] = PurchaseOrderStatus::find($this->getAttribute('purchase_order_status_id'))->name ?? __('Pending');

            Arr::forget($data, 'old_values.purchase_order_status_id');
            Arr::forget($data, 'new_values.purchase_order_status_id');
        }

        if (Arr::has($data, 'new_values.warehouse_id')) {
            $data['old_values']['warehouse'] = Warehouse::find($this->getOriginal('warehouse_id'))->contactInformation->name ?? '';
            $data['new_values']['warehouse'] = Warehouse::find($this->getAttribute('warehouse_id'))->contactInformation->name ?? '';

            Arr::forget($data, 'old_values.warehouse_id');
            Arr::forget($data, 'new_values.warehouse_id');
        }

        if (Arr::has($data, 'new_values.supplier_id')) {
            $data['old_values']['supplier'] = Supplier::find($this->getOriginal('supplier_id'))->contactInformation->name ?? '';
            $data['new_values']['supplier'] = Supplier::find($this->getAttribute('supplier_id'))->contactInformation->name ?? '';

            Arr::forget($data, 'old_values.supplier_id');
            Arr::forget($data, 'new_values.supplier_id');
        }

        if ($this->auditEvent == 'created') {
            $data['custom_message'] = __('Purchase order created');
            $data['old_values'] = null;
            $data['new_values'] = ['message' => $data['custom_message']];
        } elseif ($this->auditEvent == 'updated') {
            foreach (Arr::get($data, 'new_values') as $attribute => $value) {
                if ($attribute == 'message') {
                    $data['custom_message'] = Arr::get($data, 'new_values.message', '');
                } elseif (in_array($attribute, PurchaseOrder::$columnBoolean)) {
                    $condition = $this->getAttribute($attribute) ? __('Added') : __('Removed');

                    $data['custom_message'] .= __(':condition :attribute <br/>', [
                        'attribute' => str_replace('_', ' ', $attribute),
                        'condition' => $condition,
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
                } else {
                    $field = Arr::get(static::$columnTitle, $attribute, str_replace('_', ' ', ucfirst($attribute)));

                    $data['custom_message'] .= static::setAuditMessage($field, $data, $attribute) . ' <br/>';
                }
            }
        } else {
            $data['custom_message'] = Arr::get($this->auditCustomNew, 'message', '');
        }

        return $data;
    }

    public static function getAudits(Request $request, PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder = $purchaseOrder->load(
            'audits.user.contactInformation',
            'purchaseOrderItems.audits.user.contactInformation'
        );

        $audits = $purchaseOrder->audits;

        $purchaseOrder->purchaseOrderItems->map(function ($purchaseOrderItem, $key) use ($audits) {
            $purchaseOrderItem->audits->map(function ($audit, $key) use ($audits) {
                $audits->push($audit);
            });
        });

        return app('audit')->prepareEachAudits($request, $audits);
    }
}

