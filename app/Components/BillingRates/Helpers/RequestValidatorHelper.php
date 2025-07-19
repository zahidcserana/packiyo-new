<?php

namespace App\Components\BillingRates\Helpers;

use Illuminate\Support\Arr;

class RequestValidatorHelper
{
    /**
     * @param array $rateSettings
     * @param array $values
     * @return bool
     */
    public static function compareExistingSettingForMatchHasOrderTagWithNewValues(array $rateSettings, array $values = []): bool
    {
        $data = Arr::get($rateSettings, 'match_has_order_tag', []);
        return self::compareTags($data, $values);
    }

    /**
     * @param array $rateSettings
     * @param array $values
     * @return bool
     */
    public static function compareExistingRateForMatchHasNotOrderTagWithNewValues(array $rateSettings, array $values = []): bool
    {
        $data = Arr::get($rateSettings, 'match_has_not_order_tag', []);
        return self::compareTags($data, $values);
    }

    /**
     * @param array $setting
     * @param array $values
     * @return bool
     */
    public static function compareTags(array $setting, array $values = []): bool
    {
        $diff1 = array_diff($setting, $values);
        $diff2 = array_diff($values, $setting);
        return empty($diff1) && empty($diff2);
    }

    /**
     * @param array $settings
     * @return bool
     */
    public static function rateContainsHasOrderTag(array $settings): bool
    {
        $data = Arr::get($settings, 'match_has_order_tag', []);
        return !empty($data);
    }

    /**
     * @param array $settings
     * @return bool
     */
    public static function rateContainsHasNotOrderTag(array $settings): bool
    {
        $data = Arr::get($settings, 'match_has_not_order_tag', []);
        return !empty($data);
    }

    public static function shippingBoxIsPartOfRateDefinition(int $shippingBox, array $shippingBoxCustomers, int $customer): bool
    {
        return in_array($shippingBox, Arr::get($shippingBoxCustomers, $customer, []));
    }
}
