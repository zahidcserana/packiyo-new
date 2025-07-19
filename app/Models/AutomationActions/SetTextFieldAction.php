<?php

namespace App\Models\AutomationActions;

use App\Interfaces\AutomationBaseObjectInterface;
use App\Traits\Automation\SetTextFieldActionTrait;
use App\Interfaces\AutomationActionInterface;
use App\Models\AutomationAction;

class SetTextFieldAction extends AutomationAction implements AutomationActionInterface, AutomationBaseObjectInterface
{
    use SetTextFieldActionTrait;
    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::class,
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Set Text Field';
    }
}
