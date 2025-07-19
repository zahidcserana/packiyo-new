<?php

namespace App\Components;

use App\Components\BillingRates\RequestValidator\BillingRequestValidator;
use App\Exceptions\BillingException;
use App\Models\RateCard;
use App\Models\BillingRate;
use Illuminate\Support\Arr;

class BillingRateComponent extends BaseComponent
{
    public function store($input, $type, RateCard $rateCard)
    {
        $input['type'] = $type;
        $input['rate_card_id'] = $rateCard->id;
        $input['is_enabled'] = Arr::get($input, 'is_enabled', 0);

        return BillingRate::create($input);
    }

    private function validatePurchaseOrderBilling(RateCard $rateCard): void
    {
        //TODO Validation for new billing rate not define, requires order Tags and product tags consideration. commenting this for the moment.
        /*if ($this->countBillingByType($rateCard, BillingRate::PURCHASE_ORDER) > 0) {
            throw new BillingException('Purchase Order billing rate already exists');
        }*/
    }

    private function countBillingByType(RateCard $rateCard, string $type): int
    {
        return $rateCard?->billingRates->where('type', $type)->count();
    }

    public function update($input, BillingRate $billingRate): BillingRate
    {
        $input['is_enabled'] = Arr::get($input, 'is_enabled', 0);

        $billingRate->update($input);

        return $billingRate;
    }

    public function destroy(BillingRate $billingRate): BillingRate
    {
        $billingRate->delete();

        return $billingRate;
    }

    public function searchQuery(string $search, $billingRates)
    {
        $term = $search . '%';

        $billingRates->where('name', 'like', $term);

        return $billingRates;
    }

    public function getQuery(array $filterInputs = [])
    {
        $billingRateCollection = BillingRate::where('billing_rates.rate_card_id', $filterInputs['rate_card_id'])
            ->where(function ($query) use ($filterInputs) {
                // Find by filter result

                // Allocated
                if (isset($filterInputs['type'])) {
                    $query->where('billing_rates.type', $filterInputs['type']);
                }

            })
            ->select('billing_rates.*')
            ->groupBy('billing_rates.id')
            ->orderBy('billing_rates.id', 'desc');

        // Show deleted products
        if (isset($filterInputs['show_deleted'])) {
            if ($filterInputs['show_deleted'] == 1) {
                $billingRateCollection->onlyTrashed();
            } elseif ($filterInputs['show_deleted'] == 2) {
                $billingRateCollection->withTrashed();
            }
        }

        return $billingRateCollection;
    }

    public function storePurchaseOrderRate(array $input, RateCard $rateCard): void
    {
        $this->validatePurchaseOrderBilling($rateCard);
        $this->store($input, BillingRate::PURCHASE_ORDER, $rateCard);
    }

    public function updatePurchaseOrderRate(array $input, BillingRate $billingRate, RateCard $rateCard): void
    {
        $this->validatePurchaseOrderBilling($rateCard);
        $this->update($input, $billingRate);
    }

    /**
     * @param array $input
     * @param RateCard $rateCard
     * @return array
     */
    public function storePackagingRate(array $input, RateCard $rateCard): array
    {
        [$validated, $errorMessage] = app(BillingRequestValidator::class)->validatePackageRate($input, $rateCard);
        return !$validated ? [$validated, $errorMessage] : [$this->store($input, BillingRate::PACKAGING_RATE, $rateCard), null];
    }

    /**
     * @param array $input
     * @param BillingRate $billingRate
     * @param RateCard $rateCard
     * @return array
     */
    public function updatePackagingRate(array $input, BillingRate $billingRate, RateCard $rateCard): array
    {
        [$validated, $errorMessage] = app(BillingRequestValidator::class)->validatePackageRate($input, $rateCard, $billingRate);
        return !$validated ? [$validated, $errorMessage] : [$this->update($input, $billingRate),null];
    }

    /**
     * @param array $input
     * @param RateCard $rateCard
     * @return array
     */
    public function storeShippingRate(array $input, RateCard $rateCard): array
    {
        [$validated, $errorMessage] = app(BillingRequestValidator::class)->validateShippingRate($input, $rateCard);
        return !$validated
            ? [$validated, $errorMessage]
            : [$this->store($input, BillingRate::SHIPMENTS_BY_SHIPPING_LABEL, $rateCard), null];
    }

    /**
     * @param array $input
     * @param BillingRate $billingRate
     * @param RateCard $rateCard
     * @return array
     */
    public function updateShippingRate(array $input, BillingRate $billingRate, RateCard $rateCard): array
    {
        [$validated, $errorMessage] = app(BillingRequestValidator::class)->validateShippingRate($input, $rateCard, $billingRate);
        return !$validated ? [$validated, $errorMessage] : [$this->store($input, BillingRate::SHIPMENTS_BY_SHIPPING_LABEL, $rateCard), null];
    }
}
