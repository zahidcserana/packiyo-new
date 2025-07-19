<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\Automations\AppliesToOperationTags;
use App\Models\AutomationConditions\OrderTagsCondition;
use App\Models\Tag;

trait AddsOrderTagsCondition
{
    protected function addOrderTagsCondition(AutomationChoices $automationChoices): OrderTagsCondition|array
    {
        $ownerCustomer = $automationChoices->getOwnerCustomer();
        $appliesTo = $this->choice(
            __('Which tags should be matched?'),
            collect(AppliesToOperationTags::cases())->pluck('value')->toArray()
        );
        $tagNames = array_map('trim', str_getcsv($this->anticipate(
            __('Which tags should trigger the automation? (Separate multiple with commas.)'),
            Tag::where('customer_id', $ownerCustomer->id)->pluck('name')->toArray()
        )));
        $tags = static::getOrCreateTags($ownerCustomer->id, $tagNames);

        $trigger = new OrderTagsCondition([
            'applies_to' => $appliesTo,
        ]);
        $callback = fn (OrderTagsCondition $trigger) => $trigger->tags()->attach($tags->pluck('id')->toArray());

        return [$trigger, $callback];
    }
}
