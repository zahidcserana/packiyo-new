<?php

namespace App\Traits\Audits;

use App\Models\Location;
use App\Models\LocationType;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

trait LocationAudit
{
    use AuditTrait;

    public static $columnTitle = [
        'priority_counting_requested_at' => 'Priority counting',
        'last_counted_at' => 'Last counted date',
    ];

    /**
     * @param array $data
     * @return array $data
     */
    public function transformAudit(array $data): array
    {
        $data['custom_message'] = '';

        if (Arr::has($data, 'new_values.location_type_id')) {
            $data['old_values']['location_type'] = LocationType::find($this->getOriginal('location_type_id'))->name ?? '';
            $data['new_values']['location_type'] = LocationType::find($this->getAttribute('location_type_id'))->name ?? '';

            Arr::forget($data, 'old_values.location_type_id');
            Arr::forget($data, 'new_values.location_type_id');
        }

        if ($this->auditEvent == 'created') {
            $data['custom_message'] = __('Location created');
            $data['old_values'] = null;
            $data['new_values'] = ['message' => $data['custom_message']];
        } elseif ($this->auditEvent == 'updated') {
            foreach (Arr::get($data, 'new_values') as $attribute => $value) {
                if ($attribute == 'message') {
                    $data['custom_message'] = Arr::get($data, 'new_values.message', '');
                } elseif (in_array($attribute, Location::$columnBoolean)) {
                    $condition = $this->getAttribute($attribute) ? __('Enabled') : __('Disabled');

                    $data['custom_message'] .= __(':condition :attribute <br/>', [
                        'attribute' => str_replace('_', ' ', $attribute),
                        'condition' => $condition,
                    ]);
                } else {
                    $field = Arr::get(static::$columnTitle, $attribute, str_replace('_', ' ', ucfirst($attribute)));

                    $data['custom_message'] .= static::setAuditMessage($field, $data, $attribute) . ' <br/>';
                }
            }
        }

        return $data;
    }

    public static function getAudits(Request $request, Location $location)
    {
        $location = $location->load('audits.user.contactInformation');
        $audits = $location->audits;

        return app('audit')->prepareEachAudits($request, $audits);
    }
}

