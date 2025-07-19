<?php

namespace App\Rules\BillingRates;

use App\Models\BillingRate;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;

class ShipmentByBoxRule implements Rule
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
        $shippingBoxes = json_decode($value['shipping_boxes'], true);
        $noOtherRateApplies = Arr::get($value, 'if_no_other_rate_applies');

        foreach ($rates as $rate) {
            $settings = $rate['settings'];
            $boxes = json_decode($settings['shipping_boxes']);

            if ($noOtherRateApplies && Arr::get($settings, 'if_no_other_rate_applies')) {
                return false;
            }

            foreach ($shippingBoxes as $box) {
                $result = in_array($box, $boxes);

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
