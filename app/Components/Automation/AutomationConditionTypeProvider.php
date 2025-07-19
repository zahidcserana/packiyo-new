<?php

namespace App\Components\Automation;

use App\Interfaces\AutomationConditionInterface;
use App\Models\Automations\AutomationConditionType;
use App\Models\Automations\IdentifiesUsingSlugs;
use Illuminate\Support\Collection;

class AutomationConditionTypeProvider
{
    use IdentifiesUsingSlugs;

    protected Collection $conditions;

    public function __construct(AutomationConditionInterface ...$conditions)
    {
        $this->conditions = collect($conditions)->mapWithKeys(
            static fn (AutomationConditionInterface $conditionType) => static::indexTrigger($conditionType)
        );
    }

    protected static function indexTrigger(AutomationConditionInterface $conditionType): array
    {
        return [static::classToSlug($conditionType::class) =>
            new AutomationConditionType($conditionType::class, $conditionType->title)];
    }

    public function get(string $resourceId): AutomationConditionType|null
    {
        return $this->conditions->get($resourceId);
    }

    public function all(): array
    {
        return $this->conditions->all();
    }
}
