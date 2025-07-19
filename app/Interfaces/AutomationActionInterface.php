<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface AutomationActionInterface
{
    public static function getSupportedEvents(): array;

    public static function appliesToMany(): bool;

    public function relationshipsForClone(AutomationActionInterface $trigger): void;

    public function run(AutomatableEvent $event): void;

    public function automation(): BelongsTo;

    public static function getBuilderColumns();

    public function getTitleAttribute(): String;

    public function getDescriptionAttribute(): String;
}
