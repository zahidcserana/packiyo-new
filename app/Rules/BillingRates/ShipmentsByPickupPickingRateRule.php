<?php

namespace App\Rules\BillingRates;

use App\Models\BillingRate;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;

class ShipmentsByPickupPickingRateRule implements Rule
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
        $productProfiles = json_decode($value['product_profiles'], true);
        $withoutProfile = Arr::get($value, 'without_profile');
        $noOtherRateApplies = Arr::get($value, 'if_no_other_rate_applies');

        foreach ($rates as $rate) {
            $settings = $rate['settings'];
            $profiles = json_decode($settings['product_profiles']);

            if ($noOtherRateApplies && Arr::get($settings, 'if_no_other_rate_applies')) {
                return false;
            }

            if ($withoutProfile && Arr::get($settings, 'without_profile')) {
                return false;
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
