<?php

namespace App\Components\BillingRates\Helpers;

class TagHelper
{
    public static function matchOrderTags(array $tags, array $settingTags): bool
    {
        $tagNames = array_map('strtolower', $tags);
        $matchHasOrderTag = array_map('strtolower', $settingTags);
        return empty(array_diff($matchHasOrderTag, $tagNames));
    }

    public static function matchNotOrderTags(array $tags, array $settingTags): bool
    {
        $tagNames = array_map('strtolower', $tags);
        $matchHasNotOrderTag = array_map('strtolower', $settingTags);
        return empty(array_intersect($tagNames, $matchHasNotOrderTag));
    }
}
