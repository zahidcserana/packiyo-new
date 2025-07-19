<?php

namespace App\Models\AutomationActions;

use App\Models\Automations\OrderTextField;
use App\Traits\Automation\SetTextFieldActionTrait;
use App\Interfaces\AutomationActionInterface;
use App\Models\AutomationAction;

class SetIncotermsAction extends AutomationAction implements AutomationActionInterface
{
    use SetTextFieldActionTrait;

    protected $attributes = [
        'field_name' => OrderTextField::INCOTERMS,
    ];
    public static function getBuilderColumns(): array
    {
        return [
            'type' => SetTextFieldAction::class,
            'field_name' => OrderTextField::INCOTERMS->value
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Set incoterms';
    }

    public function getDescriptionAttribute(): string
    {
        return sprintf('%s as %s', $this->getTitleAttribute(), $this->text_field_value);
    }
}
