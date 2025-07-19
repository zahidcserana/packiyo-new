<?php

namespace App\Traits\Audits;

use App\Models\Tote;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

trait ToteAudit
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
            $data['custom_message'] = __('Tote created');
            $data['old_values'] = null;
            $data['new_values'] = ['message' => $data['custom_message']];
        } elseif ($this->auditEvent == 'updated') {
            foreach (Arr::get($data, 'new_values') as $attribute => $value) {
                if ($attribute == 'message') {
                    $data['custom_message'] = Arr::get($data, 'new_values.message', '');
                } else {
                    $data['custom_message'] .= static::setAuditMessage(ucfirst($attribute), $data, $attribute) . ' <br/>';
                }
            }
        } elseif ($this->auditEvent == 'deleted') {
            $data['custom_message'] = __('Tote deleted');
            $data['old_values'] = null;
            $data['new_values'] = ['message' => $data['custom_message']];
        } elseif (in_array($this->auditEvent, ['picked', 'removed'])) {
            $data['custom_message'] = Arr::get($data, 'new_values.message', '');
        }

        return $data;
    }

    public static function getAudits(Request $request, Tote $tote)
    {
        $tote = $tote->load('audits.user.contactInformation');
        $audits = $tote->audits;

        return app('audit')->prepareEachAudits($request, $audits);
    }
}

