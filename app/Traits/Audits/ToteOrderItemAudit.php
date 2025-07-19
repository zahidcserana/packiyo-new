<?php

namespace App\Traits\Audits;

use Illuminate\Support\Arr;

trait ToteOrderItemAudit
{
    use AuditTrait;

    /**
     * @param array $data
     * @return array $data
     */
    public function transformAudit(array $data): array
    {
        $data['custom_message'] = '';

        if (in_array($this->auditEvent, ['picked', 'removed'])) {
            $data['custom_message'] = Arr::get($data, 'new_values.message', '');
        }

        return $data;
    }

}
