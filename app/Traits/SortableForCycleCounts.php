<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait SortableForCycleCounts
{
    public function scopeSortedForCycleCounts(Builder $query)
    {
        return $query->orderByRaw("priority_counting_requested_at IS NOT NULL DESC, priority_counting_requested_at ASC")
            ->orderByRaw("priority_counting_requested_at IS NULL AND last_counted_at IS NULL DESC")
            ->orderByRaw("last_counted_at IS NOT NULL ASC, last_counted_at ASC");
    }
}
