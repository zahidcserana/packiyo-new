<?php

namespace App\Components\Automation;

use App\Models\Automations\AutomatableEvent;
use App\Models\Automations\IdentifiesUsingSlugs;
use Illuminate\Support\Collection;

class AutomatableEventProvider
{
    use IdentifiesUsingSlugs;

    protected Collection $events;

    public function __construct(AutomatableEvent ...$events)
    {
        $this->events = collect($events)->mapWithKeys(
            static fn (AutomatableEvent $eventType) => static::indexEvent($eventType)
        );
    }

    protected static function indexEvent(AutomatableEvent $eventType): array
    {
        return [static::classToSlug($eventType->type) => $eventType];
    }

    public function get(string $resourceId): AutomatableEvent|null
    {
        return $this->events->get($resourceId);
    }

    public function all(): array
    {
        return $this->events->all();
    }
}
