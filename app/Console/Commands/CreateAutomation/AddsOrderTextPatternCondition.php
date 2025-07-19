<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\Automations\OrderTextField;
use App\Models\Automations\PatternComparison;
use App\Models\AutomationConditions\OrderTextPatternCondition;

trait AddsOrderTextPatternCondition
{
    protected function addOrderTextPatternCondition(AutomationChoices $automationChoices): OrderTextPatternCondition
    {
        $fieldName = $this->choice(
            __("Which field should be matched against the pattern?"),
            collect(OrderTextField::readable())->pluck("value")->toArray()
        );
        $operator = $this->choice(
            __("How should the field be matched?"),
            collect(PatternComparison::cases())->pluck("value")->toArray()
        );
        // TODO: Validate input.
        $pattern = trim($this->ask(__(
            "What is the pattern you want to match?\n"
            . "  - {#} One number.\n"
            . "  - {@} One letter.\n"
            . "  - {#?} Zero or one number.\n"
            . "  - {@?} Zero or one letter.\n"
            . "  - {#+} One or more numbers.\n"
            . "  - {@+} One or more letters.\n"
            . "  - {#+?} Zero or more numbers.\n"
            . "  - {@+?} Zero or more letters."
        )));

        return new OrderTextPatternCondition([
            "field_name" => $fieldName,
            "text_pattern" => $pattern,
            "comparison_operator" => $operator
        ]);
    }
}
