<?php

namespace App\Console\Commands\CreateAutomation;

use App\Models\Tag;
use Illuminate\Support\Collection;

trait GetsOrCreatesTags
{
    protected static function getOrCreateTags(int $customerId, array $tagNames): Collection
    {
        return collect($tagNames)
            ->map(fn (string $tagName) => Tag::firstOrCreate(['customer_id' => $customerId, 'name' => $tagName]));
    }
}
