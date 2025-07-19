<?php

namespace App\Traits\Audits;

use App\Models\Product;
use Illuminate\Support\Arr;

trait PurchaseOrderItemAudit
{
    use AuditTrait;

    /**
     * @param array $data
     * @return array $data
     */
    public function transformAudit(array $data): array
    {
        $data['custom_message'] = '';

        if ($this->auditEvent == 'created') {
            if (Arr::has($data, 'new_values.product_id') && empty($data['old_values'])) {
                $data['custom_message'] = __('Added :quantity x :sku', [
                    'quantity' => $this->getAttribute('quantity'),
                    'sku' => Product::find($this->getAttribute('product_id'))->sku,
                ]);
            }
        } elseif ($this->auditEvent == 'updated') {
            if (Arr::hasAny($data, ['new_values.quantity', 'new_values.quantity_received', 'new_values.quantity_sell_ahead'])) {
                foreach ($data['new_values'] as $attribute => $value) {
                    $data['custom_message'] .= __(':attribute changed from "<em>:old</em>" to "<em>:new</em>" for :sku <br/>', [
                        'attribute' => str_replace('_', ' ', ucfirst($attribute)),
                        'new' => $this->getAttribute($attribute),
                        'old' => Arr::get($data, 'old_values.' . $attribute),
                        'sku' => Product::find($this->getAttribute('product_id'))->sku,
                    ]);
                }
            }
        }

        return $data;
    }
}
