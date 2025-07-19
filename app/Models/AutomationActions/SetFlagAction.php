<?php

namespace App\Models\AutomationActions;

use App\Interfaces\AutomationBaseObjectInterface;
use App\Traits\Automation\SetFlagActionTrait;
use App\Interfaces\AutomationActionInterface;
use App\Models\AutomationAction;

class SetFlagAction extends AutomationAction implements AutomationActionInterface, AutomationBaseObjectInterface
{
    use SetFlagActionTrait;
    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::class
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Set Flag';
    }
}
