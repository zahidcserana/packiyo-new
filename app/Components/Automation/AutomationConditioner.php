<?php

namespace App\Components\Automation;

use App\Exceptions\AutomationException;
use App\Interfaces\AutomationConditionInterface;
use Illuminate\Support\Collection;

class AutomationConditioner
{
    protected array $conditions;
    protected array $conditionsForSingleByEvents;
    protected array $conditionsForManyByEvents;

    public function __construct(AutomationConditionInterface ...$conditions)
    {
        $this->conditions = $conditions;
        [$this->conditionsForSingleByEvents, $this->conditionsForManyByEvents] = self::sortByEvents($conditions);
    }

    public function getConditions(array $events = null, bool $forMany = false): Collection
    {
        return $events ? $this->getConditionsByEvents($events, $forMany) : collect($this->conditions);
    }

    private static function sortByEvents(array $conditions): array
    {
        $conditionsForSingleByEvents = [];
        $conditionsForManyByEvents = [];

        foreach ($conditions as $condition) {
            foreach ($condition::getSupportedEvents() as $event) {
                $conditionsForSingleByEvents[$event][] = $condition;

                if ($condition::appliesToMany()) {
                    $conditionsForManyByEvents[$event][] = $condition;
                }
            }
        }

        return [$conditionsForSingleByEvents, $conditionsForManyByEvents];
    }

    protected function getConditionsByEvents(array $events, bool $forMany = false): Collection
    {
        $triggersByEvents = $forMany ? $this->conditionsForManyByEvents : $this->conditionsForSingleByEvents;

        if (!empty(array_diff($events, array_keys($triggersByEvents)))) {
            throw new AutomationException('Unknown event given.');
        }

        return collect($events)
            ->flatMap(fn (string $event) => $triggersByEvents[$event])
            ->unique()
            ->values();
    }
}
