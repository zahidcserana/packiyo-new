<?php

namespace App\Components\BillingRates\Helpers;

class BillingRateCacheHelper
{
    public static function flattenArray(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                // Check if the current depth is the last depth
                if (!empty($value) && is_array(reset($value))) {
                    // If it is, recursively flatten the array
                    $result = array_merge($result, self::flattenArray($value));
                } else {
                    // Otherwise, add the key and value to the result
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }
}
