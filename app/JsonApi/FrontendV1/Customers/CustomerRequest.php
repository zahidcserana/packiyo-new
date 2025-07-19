<?php

namespace App\JsonApi\FrontendV1\Customers;

use App\Http\Requests\Customer\StoreRequest;
use App\Http\Requests\Customer\UpdateRequest;
use Illuminate\Support\Arr;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class CustomerRequest extends ResourceRequest
{

    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        if ($this->isCreating()) {
            $request = new StoreRequest($this->input('data.attributes'));
        } else {
            $request = new UpdateRequest($this->input('data.attributes'));
        }

        $rules = $request->rules();

        return $rules;
    }

    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        $input = $this->input();

        if ($contactInformationData = Arr::get($input, 'data.attributes.contact_information_data')) {
            $input['data']['attributes']['parent_customer_id'] = $contactInformationData['parent_customer_id'];
            $input['data']['attributes']['contact_information']['name'] = $contactInformationData['name'];
            $input['data']['attributes']['contact_information']['company_name'] = $contactInformationData['company_name'];
            $input['data']['attributes']['contact_information']['company_number'] = $contactInformationData['company_number'];
            $input['data']['attributes']['contact_information']['country_id'] = $contactInformationData['country_id'];
            unset($input['data']['attributes']['contact_information_data']);
        }

        if ($userSettingsData = Arr::get($input, 'data.attributes.user_settings_data')) {
            $input['data']['attributes']['user_settings'] = $userSettingsData;
            unset($input['data']['attributes']['user_settings_data']);
        }

        if ($customerSettingsData = Arr::get($input, 'data.attributes.customer_settings_data')) {
            $input['data']['attributes']['locale'] = $customerSettingsData['locale'];
            $input['data']['attributes']['weight_unit'] = $customerSettingsData['weight_unit'];
            $input['data']['attributes']['dimension_unit'] = $customerSettingsData['dimension_unit'];
            $input['data']['attributes']['currency'] = $customerSettingsData['currency'];
            $input['data']['attributes']['allow_child_customers'] = $customerSettingsData['allow_child_customers'];
            unset($input['data']['attributes']['customer_settings_data']);
        }

        if ($rateCardsData = Arr::get($input, 'data.attributes.rate_cards_data')) {
            $input['data']['attributes']['rate_cards']['primary_rate_card_id'] = $rateCardsData['primary_rate_card_id'];
            $input['data']['attributes']['rate_cards']['secondary_rate_card_id'] = $rateCardsData['secondary_rate_card_id'];
            unset($input['data']['attributes']['rate_cards_data']);
        }

        $this->replace($input);
    }

}
