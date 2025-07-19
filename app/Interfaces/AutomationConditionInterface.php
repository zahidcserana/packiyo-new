<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface AutomationConditionInterface
{
    public static function getSupportedEvents(): array;

    public static function appliesToMany(): bool;

    public function relationshipsForClone(AutomationConditionInterface $condition): void;

    public function match(AutomatableEvent $event): bool;

    public function automation(): BelongsTo;

    public static function getBuilderColumns();

    public function getTitleAttribute(): String;

    public function getDescriptionAttribute(): String;
}
