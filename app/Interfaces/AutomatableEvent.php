<?php

namespace App\Interfaces;

interface AutomatableEvent
{
    public function getOperation(): AutomatableOperation;

    public static function getTitle(): String;
}
