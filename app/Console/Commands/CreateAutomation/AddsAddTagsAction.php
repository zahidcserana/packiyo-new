<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\AutomationActions\AddTagsAction;
use App\Models\Tag;

trait AddsAddTagsAction
{
    protected function addAddTagsAction(AutomationChoices $automationChoices): AddTagsAction|array
    {
        $ownerCustomer = $automationChoices->getOwnerCustomer();
        $tagNames = array_map('trim', str_getcsv($this->anticipate(
            __('Which tags do you want to set? (Separate multiple with commas.)'),
            Tag::where('customer_id', $ownerCustomer->id)->pluck('name')->toArray()
        )));
        $tags = static::getOrCreateTags($ownerCustomer->id, $tagNames);
        $action = new AddTagsAction();
        $callback = fn (AddTagsAction $action) => $action->tags()->attach($tags->pluck('id')->toArray());

        return [$action, $callback];
    }
}
