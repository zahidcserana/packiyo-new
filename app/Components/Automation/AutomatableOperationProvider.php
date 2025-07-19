<?php

namespace App\Components\Automation;

use App\Models\Automations\AutomatableOperation;
use App\Models\Automations\IdentifiesUsingSlugs;
use Illuminate\Support\Collection;

class AutomatableOperationProvider
{
    use IdentifiesUsingSlugs;

    protected Collection $operations;

    public function __construct(AutomatableOperation ...$operations)
    {
        $this->operations = collect($operations)->mapWithKeys(
            static fn (AutomatableOperation $operationType) => static::indexOperation($operationType)
        );
    }

    protected static function indexOperation(AutomatableOperation $operationType): array
    {
        return [static::classToSlug($operationType->type::getOperationClass()) => $operationType];
    }

    public function get(string $resourceId): AutomatableOperation|null
    {
        return $this->operations->get($resourceId);
    }

    public function all(): array
    {
        return $this->operations->all();
    }
}
