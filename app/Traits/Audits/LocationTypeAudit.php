<?php

namespace App\Traits\Audits;

use App\Models\LocationType;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

trait LocationTypeAudit
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
            $data['custom_message'] = __('Location Type created');
            $data['old_values'] = null;
            $data['new_values'] = ['message' => $data['custom_message']];
        } elseif ($this->auditEvent == 'updated') {
            foreach (Arr::get($data, 'new_values') as $attribute => $value) {
                if ($attribute == 'message') {
                    $data['custom_message'] = Arr::get($data, 'new_values.message', '');
                } elseif (in_array($attribute, LocationType::$columnBoolean)) {
                    $condition = $this->getAttribute($attribute) ? __('Enabled') : __('Disabled');

                    $data['custom_message'] .= __(':condition :attribute <br/>', [
                        'attribute' => str_replace('_', ' ', $attribute),
                        'condition' => $condition,
                    ]);
                } else {
                    $data['custom_message'] .= static::setAuditMessage(ucfirst($attribute), $data, $attribute) . ' <br/>';
                }
            }
        }

        return $data;
    }

    public static function getAudits(Request $request, LocationType $locationType)
    {
        $locationType = $locationType->load('audits.user.contactInformation');
        $audits = $locationType->audits;

        return app('audit')->prepareEachAudits($request, $audits);
    }
}

