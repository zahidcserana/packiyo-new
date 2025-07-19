<?php

namespace App\Traits\Audits;

use Illuminate\Support\Arr;
use Webpatser\Countries\Countries;

trait ContactInformationAudit
{
    /**
     * @param array $data
     * @return array $data
     */
    public function transformAudit(array $data): array
    {
        $data['custom_message'] = '';

        if (Arr::has($data, 'new_values.shipping_contact_information.country_id')) {
            $data['old_values']['shipping_contact_information']['country'] = Countries::find($this->getOriginal('country_id'))->name ?? '';
            $data['new_values']['shipping_contact_information']['country'] = Countries::find($this->getAttribute('country_id'))->name ?? '';
        }

        if (Arr::has($data, 'new_values.billing_contact_information.country_id')) {
            $data['old_values']['billing_contact_information']['country'] = Countries::find($this->getOriginal('country_id'))->name ?? '';
            $data['new_values']['billing_contact_information']['country'] = Countries::find($this->getAttribute('country_id'))->name ?? '';
        }

        if ($this->auditEvent == 'updated') {
            if (Arr::has($data, 'new_values.shipping_contact_information')) {
                $exceptData = Arr::except($data, ['new_values.shipping_contact_information.country_id']);
                $contactInformationArr = Arr::get($exceptData, 'new_values.shipping_contact_information');
                $data['custom_message'] .= 'Shipping:<br/>';
            } elseif (Arr::has($data, 'new_values.billing_contact_information')) {
                $exceptData = Arr::except($data, ['new_values.billing_contact_information.country_id']);
                $contactInformationArr = Arr::get($exceptData, 'new_values.billing_contact_information');
                $data['custom_message'] .= 'Billing:<br/>';
            }

            if (isset($contactInformationArr)) {
                // TODO: Why is this not using array_keys()? $value is not referenced.
                foreach ($contactInformationArr as $attribute => $value) {
                    if (empty($this->getOriginal($attribute))) {
                        $data['custom_message'] .= __(':attribute set to <em>":new"</em> <br/>', [
                            'attribute' => str_replace('_', ' ', ucfirst($attribute)),
                            'new' => $attribute == 'country' ? $this->getAttribute($attribute)->name : $this->getAttribute($attribute)
                        ]);
                    } else {
                        $data['custom_message'] .= __(':attribute changed from <em>":old"</em> to <em>":new"</em> <br/>', [
                            'attribute' => str_replace('_', ' ', ucfirst($attribute)),
                            'old' => $this->getOriginal($attribute),
                            'new' => $attribute == 'country' ? $this->getAttribute($attribute)->name : $this->getAttribute($attribute)
                        ]);
                    }
                }
            }
        }

        return $data;
    }
}
