<?php

namespace App\Models\AutomationActions;

use App\Exceptions\AutomationException;
use App\Interfaces\AutomatableEvent;
use App\Interfaces\AutomationBaseObjectInterface;
use App\Traits\inheritanceHasParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Interfaces\AutomationActionInterface;
use App\Models\Automation;
use App\Models\AutomationAction;
use App\Models\Automations\AppliesToMany;
use App\Models\Automations\OrderAutomation;
use App\Models\Automations\PurchaseOrderAutomation;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RunFirstMatchingOfGroupAction extends AutomationAction
    implements AutomationActionInterface, AutomationBaseObjectInterface
{
    use HasFactory, inheritanceHasParent, AppliesToMany;

    public function automations(): HasMany
    {
        return $this->hasMany(Automation::class, 'group_action_id');
    }

    public static function getSupportedEvents(): array
    {
        return array_merge(OrderAutomation::getSupportedEvents(), PurchaseOrderAutomation::getSupportedEvents());
    }

    public static function loadForCommand(): array
    {
        return ['automations'];
    }

    public function relationshipsForClone(AutomationActionInterface $action): void
    {
        if (!$action instanceof RunFirstMatchingOfGroupAction) {
            throw new AutomationException('Wrong subtype of condition.');
        }

        $action->automations()->attach($this->automations()->pluck('id')->toArray());
        $action->save();
    }

    public function run(AutomatableEvent $event): void
    {
        foreach ($this->automations as $automation) {
            if ($automation->match($event)) {
                $automation->run($event);
                break;
            }
        }
    }

    public static function getBuilderColumns(): array
    {
        return [
            'type' => self::class,
        ];
    }

    public function getTitleAttribute(): String
    {
        return 'Run First Matching of Group';
    }

    public function getDescriptionAttribute(): String
    {
        return $this->getTitleAttribute();
    }
}
