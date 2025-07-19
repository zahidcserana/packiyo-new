<?php

namespace App\Models\Automations;

class AutomationActionType
{
    public string $type;
    public string $title;

    public function __construct(string $type, string $title)
    {
        $this->type = $type;
        $this->title = $title;
    }
}
