<?php

namespace App\Models\AutomationActions;

use App\Traits\Automation\SetFlagActionTrait;
use App\Interfaces\AutomationActionInterface;
use App\Models\AutomationAction;
use App\Models\Automations\OrderFlag;

class SetOperatorHoldAction extends AutomationAction implements AutomationActionInterface
{
    use SetFlagActionTrait;

    protected $attributes = [
        'field_name' => OrderFlag::OPERATOR_HOLD,
    ];
    public static function getBuilderColumns(): array
    {
        return [
            'type' => SetFlagAction::class,
            'field_name' => OrderFlag::OPERATOR_HOLD->value
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Set operator hold';
    }
}
