<?php

namespace App\Console\Commands\CreateAutomation;

use App\Console\Commands\CreateAutomation\AutomationChoices;
use App\Models\AutomationActions\AddPackingNoteAction;
use App\Models\Automations\InsertMethod;

trait AddsAddPackingNoteAction
{
    protected function addAddPackingNoteAction(AutomationChoices $automationChoices): AddPackingNoteAction
    {
        $insertMethod = $this->choice(
            __('Which field should be evaluated?'),
            collect(InsertMethod::cases())->pluck('value')->toArray()
        );

        do {
            $text = trim($this->ask(__('What is the content of the note?')));
        } while (empty($text));

        return new AddPackingNoteAction([
            'text' => $text,
            'insert_method' => $insertMethod
        ]);
    }
}
