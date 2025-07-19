<?php

namespace App\Console\Commands\CreateAutomation;

use App\Interfaces\AutomationActionInterface;
use App\Interfaces\AutomationConditionInterface;
use App\Models\Automation;
use App\Models\Automations\AppliesToCustomers;
use App\Models\Customer;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

trait OutputsAutomationTable
{
    protected function automationTable(
        Customer $ownerCustomer,
        array $targetEvents,
        Collection $addedConditions,
        Collection $addedActions,
        string $name = null,
        Carbon $revision = null,
        bool $isEnabled = null,
        AppliesToCustomers|null $appliesTo = null,
        Collection|null $targetCustomers = null,
    ): void
    {
        $details = __('Owner: :customer_name', ['customer_name' => $ownerCustomer->contactInformation->name]);

        if (!is_null($name)) {
            $details .= "\n" . __('Name: :automation_name', ['automation_name' => $name]);
        }

        if (!is_null($revision)) {
            $details .= "\n" . __('Revision: :revision', ['revision' => $revision]);
        }

        if (!is_null($isEnabled)) {
            $details .= "\n" . __('Enabled: :is_enabled', ['is_enabled' => $isEnabled ? __('yes') : __('no')]);
        }

        if (!is_null($appliesTo)) {
            $details .= "\n" . __('Applies to: :applies_to', ['applies_to' => $appliesTo->value]);
        }

        $automation = [
            $details . "\n",
            array_merge(
                [__('Events:')],
                array_map(fn (string $event) => ' - ' . $event, $targetEvents)
            )
        ];

        if (!is_null($targetCustomers) && !$targetCustomers->isEmpty()) {
            $automation[] = array_merge(
                ['', __('Applies to:')],
                $targetCustomers->map(fn (Customer $customer, int $index) =>
                    __(':index) :name (ID :id)', [
                        'index' => $index + 1,
                        'name' => $customer->contactInformation->name,
                        'id' => $customer->id
                    ]))->toArray()
            );
        }

        $automation = Arr::flatten($automation);
        $resolveConditionOrAction = fn ($conditionOrAction) => self::conditionOrActionToString($conditionOrAction);
        $conditions = $addedConditions->map($resolveConditionOrAction)->toArray();
        $actions = $addedActions->map($resolveConditionOrAction)->toArray();
        $this->table(
            [__('Automation'), __('Conditions'), __('Actions')],
            transpose_matrix([$automation, $conditions, $actions])
        );
    }

    protected static function conditionOrActionToString(
        AutomationActionInterface|AutomationConditionInterface|array $conditionOrAction
        ): string
    {
        // Has a callback.
        if (is_array($conditionOrAction)) {
            $conditionOrAction = $conditionOrAction[0];
        }

        $loadForCommand = $conditionOrAction::loadForCommand();
        $conditionOrAction->load($loadForCommand);
        $data = Arr::only($conditionOrAction->toArray(), array_merge(
            ['id', 'type', 'position', 'is_alternative'],
            $conditionOrAction->getFillable(),
            self::getLoadedRelationships($loadForCommand)
        ));

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    protected static function getLoadedRelationships(array $loaded): array
    {
        return array_map(fn (string $path) => Str::snake(explode('.', $path)[0]), $loaded);
    }

    protected function automationTableFromChoices(
        AutomationChoices $automationChoices, Collection $addedConditions, Collection $addedActions
    ): void
    {
        $this->automationTable(
            $automationChoices->getOwnerCustomer(),
            $automationChoices->getEventNames(),
            $addedConditions,
            $addedActions,
            appliesTo: $automationChoices->getAppliesTo(),
            targetCustomers: $automationChoices->getTargetCustomers()
        );
    }

    protected function automationTableFromModel(Automation $automation): void
    {
        $this->automationTable(
            $automation->customer,
            $automation->target_events,
            $automation->conditions,
            $automation->actions,
            name: $automation->name,
            revision: $automation->created_at,
            isEnabled: $automation->is_enabled,
            appliesTo: $automation->applies_to,
            targetCustomers: $automation->appliesToCustomers
        );
    }
}
