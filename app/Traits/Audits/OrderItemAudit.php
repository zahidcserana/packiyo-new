<?php

namespace App\Traits\Audits;

use App\Models\OrderItem;
use Illuminate\Support\Arr;

trait OrderItemAudit
{
    use AuditTrait;

    public static array $eventMessage = [
        'cancelled' => 'Order item with SKU: <em>":sku"</em>, and Name: <em>":name"</em> was cancelled.',
        'uncancelled' => 'Order item with SKU: <em>":sku"</em>, and Name: <em>":name"</em> was uncancelled.',
        'parent_kit' => '<em>":sku"</em> was :status for kit <em>":parent_sku"</em>.',
    ];

    /**
     * @param array $data
     * @return array $data
     */
    public function transformAudit(array $data): array
    {
        $data['custom_message'] = '';

        if ($this->auditEvent == 'created') {
            if(Arr::has($data, 'new_values.sku') && empty($data['old_values'])) {
                $data['custom_message'] = __('Added :quantity x :sku', [
                    'quantity' => $this->getAttribute('quantity'),
                    'sku' => $this->getAttribute('sku'),
                ]);
            }
        } elseif ($this->auditEvent == 'updated') {
            if(Arr::hasAny($data, ['new_values.quantity', 'new_values.quantity_shipped'])) {
                foreach($data['new_values'] as $attribute => $value) {
                    $data['custom_message'] .= __(':attribute changed from "<em>:old</em>" to "<em>:new</em>" for :sku <br/>', [
                        'attribute' => str_replace('_', ' ', ucfirst($attribute)),
                        'new' => $this->getAttribute($attribute),
                        'old' => Arr::get($data, 'old_values.' . $attribute),
                        'sku' => $this->getAttribute('sku'),
                    ]);
                }
            }

            if (Arr::has($data, 'new_values.cancelled_at')) {
                $cancel = empty(Arr::get($data, 'new_values.cancelled_at')) ? 'Uncancelled' : 'Canceled';

                $data['custom_message'] = __(':cancel :quantity x :sku', [
                    'cancel' => $cancel,
                    'quantity' => $this->getAttribute('quantity'),
                    'sku' => $this->getAttribute('sku'),
                ]);
            }
        } elseif (in_array($this->auditEvent, ['cancelled', 'uncancelled', 'parent_kit'])) {
            $data['custom_message'] = Arr::get($data, 'new_values.message', '');
        }

        return $data;
    }
}
