<?php

namespace App\Rules\BillingRates;

use App\Models\BillingRate;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Rule;

class StorageByProductRule implements Rule
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
        $productProfiles = json_decode($value['product_profiles'], true);

        foreach ($rates as $rate) {
            $settings = $rate['settings'];
            $locations = json_decode($settings['location_types']);
            $profiles = json_decode($settings['product_profiles']);

            foreach ($locationTypes as $location) {
                $result = in_array($location, $locations);

                if ($result) {
                    return false;
                }
            }

            foreach ($productProfiles as $profile) {
                $result = in_array($profile, $profiles);

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
