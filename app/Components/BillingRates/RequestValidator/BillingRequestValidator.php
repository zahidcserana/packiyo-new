<?php

namespace App\Components\BillingRates\RequestValidator;

use App\Components\BillingRates\Helpers\RequestValidatorHelper;
use App\Enums\BillingConflictsMessages;
use App\Models\BillingRate;
use App\Models\RateCard;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class BillingRequestValidator
{
    public function validateShippingRate(array $input, RateCard $rateCard, ?BillingRate $billingRate = null): array
    {
        $rates = $this->getBillingRates(
            $rateCard,
            BillingRate::SHIPMENTS_BY_SHIPPING_LABEL,
            $billingRate
        );
        $value = $input['settings'];
        $methodsSelected = json_decode($value['methods_selected'], true);
        $noOtherRateApplies = Arr::get($value, 'if_no_other_rate_applies');
        $matchHasOrderTags = Arr::get($value, 'match_has_order_tag', []);
        $matchHasNotOrderTags = Arr::get($value, 'match_has_not_order_tag', []);
        $hasGenericShippingKey = array_key_exists('is_generic_shipping', $value); //checks if settings is set
        $isGenericShipping = Arr::get($value, 'is_generic_shipping', false);

        if (empty($methodsSelected)) {
            if (!$hasGenericShippingKey) {
                return [false, 'Conflicts, please select a shipping method option or generic shipping'];
            }

            if (!$isGenericShipping) {
                return [false, 'Conflicts, please select a shipping method option or generic shipping'];
            }
        }

        if ($rates->count() < 1) {
            return [true, null];
        }

        foreach ($rates as $rate) {
            $settings = $rate['settings'];
            $methods = json_decode($settings['methods_selected'], true);
            $isGenericShippingScopeRate = Arr::get($settings, 'is_generic_shipping', false);
            $genericShippingScopeRate = empty($methods) || $isGenericShippingScopeRate;

            if ($noOtherRateApplies && Arr::get($settings, 'if_no_other_rate_applies')) {
                return [false, BillingConflictsMessages::DEFAULT_MESSAGE->value];
            }

            $genericShipping = empty($methodsSelected) || $isGenericShipping;

            if ($genericShipping) {
                if ($genericShippingScopeRate) {
                    if (
                        RequestValidatorHelper::compareExistingSettingForMatchHasOrderTagWithNewValues($settings, $matchHasOrderTags)
                        || RequestValidatorHelper::compareExistingRateForMatchHasNotOrderTagWithNewValues($settings, $matchHasNotOrderTags)
                    ) {
                        return [false, BillingConflictsMessages::DEFAULT_MESSAGE->value];
                    }
                } else {
                    if (
                        !RequestValidatorHelper::compareExistingSettingForMatchHasOrderTagWithNewValues($settings, $matchHasOrderTags)
                        && !RequestValidatorHelper::compareExistingRateForMatchHasNotOrderTagWithNewValues($settings, $matchHasNotOrderTags)
                    ) {
                        return [false, BillingConflictsMessages::DEFAULT_MESSAGE->value];
                    }
                }
            } else {
                foreach ($methodsSelected as $carrier => $carrierMethods) {
                    foreach ($carrierMethods as $method) {
                        $result = in_array($method, Arr::get($methods, $carrier, []));

                        if ($result) {
                            if (RequestValidatorHelper::rateContainsHasNotOrderTag($settings) || RequestValidatorHelper::rateContainsHasOrderTag($settings)) {
                                if (
                                    !RequestValidatorHelper::compareExistingSettingForMatchHasOrderTagWithNewValues($settings, $matchHasOrderTags)
                                    && !RequestValidatorHelper::compareExistingRateForMatchHasNotOrderTagWithNewValues($settings, $matchHasNotOrderTags)
                                ) {
                                    continue;
                                }
                                return [false, BillingConflictsMessages::DEFAULT_MESSAGE->value];
                            }
                            return [false, BillingConflictsMessages::DEFAULT_MESSAGE->value];
                        }
                    }
                }
            }
        }

        return [true, null];
    }

    public function validatePackageRate(array $input, RateCard $rateCard, ?BillingRate $billingRate = null): array
    {
        $rates = $this->getBillingRates(
            $rateCard,
            BillingRate::PACKAGING_RATE,
            $billingRate
        );
        $settings = $input['settings'];
        $shippingBoxSelected = array_key_exists('shipping_boxes_selected', $settings)
            ? json_decode($settings['shipping_boxes_selected'], true) : [];
        $customerSelected = array_key_exists('customer_selected', $settings)
            ? json_decode($settings['customer_selected'], true) : [];
        $noOtherRateApplies = Arr::get($settings, 'if_no_other_rate_applies', false);
        $matchHasOrderTags = Arr::get($settings, 'match_has_order_tag', []);
        $matchHasNotOrderTags = Arr::get($settings, 'match_has_not_order_tag', []);
        $hasCustomPackagingKey = array_key_exists('is_custom_packaging', $settings); //checks if settings is set
        $isCustomPackaging = Arr::get($settings, 'is_custom_packaging', false);

        if (empty($shippingBoxSelected)) {
            if (!$hasCustomPackagingKey) {
                return [false, 'Conflicts, please select a shipping package option or custom package'];
            }

            if (!$isCustomPackaging) {
                return [false, 'Conflicts, please select a shipping package option or custom package'];
            }
        }

        if ($rates->count() < 1) {
            return [true, null];
        }

        foreach ($rates as $rate) {
            $settingsInScope = $rate['settings'];
            $shippingBoxCustomerScopeRate = array_key_exists('shipping_boxes_selected', $settingsInScope)
                ? json_decode($settingsInScope['shipping_boxes_selected'], true)
                : [];

            $isCustomPackagingScopeRate = Arr::get($settingsInScope, 'is_custom_packaging', false);
            $customPackagingScopeRate = empty($shippingBoxCustomerScopeRate) || $isCustomPackagingScopeRate;

            if ($noOtherRateApplies && Arr::get($settingsInScope, 'if_no_other_rate_applies')) {
                return [false, BillingConflictsMessages::DEFAULT_MESSAGE->value];
            }

            $customPackaging = empty($shippingBoxSelected) || $isCustomPackaging;

            if ($customPackaging) {
                if ($customPackagingScopeRate) {
                    if (RequestValidatorHelper::compareExistingSettingForMatchHasOrderTagWithNewValues($settingsInScope, $matchHasOrderTags)
                        || RequestValidatorHelper::compareExistingRateForMatchHasNotOrderTagWithNewValues($settingsInScope, $matchHasNotOrderTags)) {
                        return [false, BillingConflictsMessages::DEFAULT_MESSAGE->value];
                    }
                } else {
                    if (
                        !RequestValidatorHelper::compareExistingSettingForMatchHasOrderTagWithNewValues($settingsInScope, $matchHasOrderTags)
                        && !RequestValidatorHelper::compareExistingRateForMatchHasNotOrderTagWithNewValues($settingsInScope, $matchHasNotOrderTags)
                    ) {
                        return [false, BillingConflictsMessages::DEFAULT_MESSAGE->value];
                    }
                }

            } else {
                foreach ($shippingBoxSelected as $customerId => $shippingBoxes) {
                    foreach ($shippingBoxes as $shippingBox) {
                        $result = RequestValidatorHelper::shippingBoxIsPartOfRateDefinition($shippingBox, $shippingBoxCustomerScopeRate, $customerId);

                        if ($result) {
                            if (RequestValidatorHelper::rateContainsHasNotOrderTag($settingsInScope) || RequestValidatorHelper::rateContainsHasOrderTag($settingsInScope)) {
                                if (
                                    !RequestValidatorHelper::compareExistingSettingForMatchHasOrderTagWithNewValues($settingsInScope, $matchHasOrderTags)
                                    && !RequestValidatorHelper::compareExistingRateForMatchHasNotOrderTagWithNewValues($settingsInScope, $matchHasNotOrderTags)
                                ) {
                                    continue;
                                }
                                return [false, BillingConflictsMessages::DEFAULT_MESSAGE->value];
                            }
                            return [false, BillingConflictsMessages::DEFAULT_MESSAGE->value];
                        }
                    }
                }
            }
        }

        return [true, null];
    }

    /**
     * @param RateCard $rateCard
     * @param string $billingRateType
     * @param BillingRate|null $billingRate
     * @return Collection
     */
    private function getBillingRates(
        RateCard $rateCard,
        string $billingRateType = BillingRate::PACKAGING_RATE,
        ?BillingRate $billingRate = null
    ): Collection
    {
        $rates = BillingRate::where('rate_card_id', $rateCard->id)
            ->where('type', $billingRateType);

        if ($billingRate) {
            $rates = $rates->where('id', '!=', $billingRate->id);
        }

        return $rates->get();
    }
}
