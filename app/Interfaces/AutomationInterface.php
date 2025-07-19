<?php

namespace App\Interfaces;

interface AutomationInterface
{
    public static function getSupportedEvents(): array;

    public static function getOperationClass(): string;

    public static function getTemplatableAttributes(): array;

    public function run(AutomatableEvent $event): void;

    public function move(int $newPosition): void;
}
