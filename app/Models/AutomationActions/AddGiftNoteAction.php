<?php

namespace App\Models\AutomationActions;

use App\Models\Automations\OrderTextField;
use App\Traits\Automation\SetTextFieldActionTrait;
use App\Interfaces\AutomationActionInterface;
use App\Models\AutomationAction;

class AddGiftNoteAction extends AutomationAction implements AutomationActionInterface
{
    use SetTextFieldActionTrait;

    protected $attributes = [
        'field_name' => OrderTextField::GIFT_NOTE,
    ];
    public static function getBuilderColumns(): array
    {
        return [
            'type' => SetTextFieldAction::class,
            'field_name' => OrderTextField::GIFT_NOTE->value,
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Add gift note';
    }
}
