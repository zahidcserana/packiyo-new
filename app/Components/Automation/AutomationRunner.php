<?php

namespace App\Components\Automation;

use App\Exceptions\AutomationException;
use App\Interfaces\AutomationActionInterface;
use Illuminate\Support\Collection;

class AutomationRunner
{
    protected array $actions;
    protected array $actionsForSingleByEvents;
    protected array $actionsForManyByEvents;

    public function __construct(AutomationActionInterface ...$actions)
    {
        $this->actions = $actions;
        [$this->actionsForSingleByEvents, $this->actionsForManyByEvents] = self::sortByEvents($actions);
    }

    public function getActions(array $events = null, bool $forMany = false): Collection
    {
        return $events ? $this->getActionsByEvents($events, $forMany) : collect($this->actions);
    }

    private static function sortByEvents(array $actions): array
    {
        $actionsForSingleByEvents = [];
        $actionsForManyByEvents = [];

        foreach ($actions as $action) {
            foreach ($action::getSupportedEvents() as $event) {
                $actionsForSingleByEvents[$event][] = $action;

                if ($action::appliesToMany()) {
                    $actionsForManyByEvents[$event][] = $action;
                }
            }
        }

        return [$actionsForSingleByEvents, $actionsForManyByEvents];
    }

    protected function getActionsByEvents(array $events, bool $forMany = false): Collection
    {
        $actionsByEvents = $forMany ? $this->actionsForManyByEvents : $this->actionsForSingleByEvents;

        if (!empty(array_diff($events, array_keys($actionsByEvents)))) {
            throw new AutomationException('Unknown event given.');
        }

        return collect($events)
            ->flatMap(fn (string $event) => $actionsByEvents[$event])
            ->unique()
            ->values();
    }
}
