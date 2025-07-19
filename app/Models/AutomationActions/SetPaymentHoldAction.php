<?php

namespace App\Models\AutomationActions;

use App\Traits\Automation\SetFlagActionTrait;
use App\Interfaces\AutomationActionInterface;
use App\Models\AutomationAction;
use App\Models\Automations\OrderFlag;

class SetPaymentHoldAction extends AutomationAction implements AutomationActionInterface
{
    use SetFlagActionTrait;

    protected $attributes = [
        'field_name' => OrderFlag::PAYMENT_HOLD,
    ];
    public static function getBuilderColumns(): array
    {
        return [
            'type' => SetFlagAction::class,
            'field_name' => OrderFlag::PAYMENT_HOLD->value
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Set payment hold';
    }
}
