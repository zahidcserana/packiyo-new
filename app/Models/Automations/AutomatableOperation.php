<?php

namespace App\Models\Automations;

class AutomatableOperation
{
    public string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function supportedEvents(): iterable
    {
        return collect($this->type::getSupportedEvents())->map(
            fn (string $supportedEventClass) => new AutomatableEvent($supportedEventClass,
                $supportedEventClass::getTitle())
        );
    }
}
