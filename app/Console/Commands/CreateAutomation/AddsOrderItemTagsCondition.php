<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\Automations\AppliesToItemsTags;
use App\Models\AutomationConditions\OrderItemTagsCondition;
use App\Models\Tag;

trait AddsOrderItemTagsCondition
{
    protected function addOrderItemTagsCondition(AutomationChoices $automationChoices): OrderItemTagsCondition|array
    {
        $ownerCustomer = $automationChoices->getOwnerCustomer();
        $appliesTo = $this->choice(
            __('Which items should be matched?'),
            collect(AppliesToItemsTags::cases())->pluck('value')->toArray()
        );
        $tagNames = array_map('trim', str_getcsv($this->anticipate(
            __('Which tags should trigger the automation? (Separate multiple with commas.)'),
            Tag::where('customer_id', $ownerCustomer->id)->pluck('name')->toArray()
        )));
        $tags = static::getOrCreateTags($ownerCustomer->id, $tagNames);
        $trigger = new OrderItemTagsCondition(['applies_to' => $appliesTo]);
        $callback = fn (OrderItemTagsCondition $trigger) => $trigger->tags()->attach($tags->pluck('id')->toArray());

        return [$trigger, $callback];
    }
}
