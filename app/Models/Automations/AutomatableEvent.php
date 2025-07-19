<?php

namespace App\Models\Automations;

class AutomatableEvent
{
    public string $type;
    public string $title;

    public function __construct(string $type, string $title)
    {
        $this->type = $type;
        $this->title = $title;
    }
}
