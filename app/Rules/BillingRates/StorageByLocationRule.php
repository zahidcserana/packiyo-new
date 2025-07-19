<?php

namespace App\Rules\BillingRates;

use App\Components\BillingRates\StorageByLocationBillingRateComponent;
use App\Models\BillingRate;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;

class StorageByLocationRule implements Rule
{
    private $rateCard;
    private $type;
    private $billingRate;

    public function __construct($rateCard, $type, $billingRate)
    {
        $this->rateCard = $rateCard;
        $this->type = $type;
        $this->billingRate = $billingRate;
    }

    public function passes($attribute, $value): bool
    {
        $rates = BillingRate::where('rate_card_id', $this->rateCard['id'])
            ->where('type', $this->type);

        if ($this->billingRate) {
            $rates = $rates->where('id', '!=', $this->billingRate['id']);
        }

        $rates = $rates->get();
        $locationTypes = json_decode($value['location_types'], true);
        $isNoLocationTypeEnable = (bool)Arr::get(
            $value,
            StorageByLocationBillingRateComponent::SETTING_NO_LOCATION_TYPE,
            false
        );

        if (! $isNoLocationTypeEnable && empty($locationTypes)) {
            return false;
        }

        foreach ($rates as $rate) {
            $settings = $rate['settings'];
            $locations = json_decode($settings['location_types']);
            $rateHasEnableNoLocationType = (bool)Arr::get(
                $settings,
                StorageByLocationBillingRateComponent::SETTING_NO_LOCATION_TYPE,
                false
            );

            if ($rateHasEnableNoLocationType && $isNoLocationTypeEnable) {
                return false;
            }

            foreach ($locationTypes as $location) {
                $result = in_array($location, $locations);

                if ($result) {
                    return false;
                }
            }
        }

        return true;
    }

    public function message(): array|string|Translator|Application|null
    {
        return __('Conflicts with existing fee');
    }
}
