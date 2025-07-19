<?php

namespace App\Models\AutomationEventConditions;

use Illuminate\Database\Eloquent\Builder;

trait ExcludesOperations
{
    public function excludedOperations(): Builder
    {
        return $this->automation->actedOnOperations()
            ->withPivot('target_event', 'created_at')
            ->wherePivot('target_event', static::getEventClass())
            ->getQuery();
    }
}
