<?php

namespace App\Traits\Audits;

trait ImageAudit
{
    /**
     * @param array $data
     * @return array $data
     */
    public function transformAudit(array $data): array
    {
        $data['custom_message'] = '';

        if ($this->auditEvent == 'created') {
            $data['custom_message'] = __('Product image added :source', ['source' => '<a href="' . $this->getAttribute('source') . '" target="_blank">click</a>']);
        } else if ($this->auditEvent == 'deleted') {
            $data['custom_message'] = __('Product image removed :source', ['source' => '<a href="' . $this->getOriginal('source') . '" target="_blank">click</a>']);
        }

        return $data;
    }
}
