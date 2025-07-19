<?php

namespace App\Rules\BillingRates;

use App\Models\BillingRate;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Rule;

class ReceivingByHourRule implements Rule
{
    private $rateCard;
    private $type;

    public function __construct($rateCard, $type)
    {
        $this->rateCard = $rateCard;
        $this->type = $type;
    }

    public function passes($attribute, $value): bool
    {
        $rate = BillingRate::where('rate_card_id', $this->rateCard['id'])
            ->where('type', $this->type)
            ->get();

        return empty(count($rate));
    }

    public function message(): array|string|Translator|Application|null
    {
        return __('Conflicts with existing fee');
    }
}
