<?php

namespace App\Components\Automation;

use App\Interfaces\AutomationActionInterface;
use App\Models\Automations\AutomationActionType;
use App\Models\Automations\IdentifiesUsingSlugs;
use Illuminate\Support\Collection;

class AutomationActionTypeProvider
{
    use IdentifiesUsingSlugs;

    protected Collection $actions;

    public function __construct(AutomationActionInterface ...$actions)
    {
        $this->actions = collect($actions)->mapWithKeys(
            static fn (AutomationActionInterface $actionType) => static::indexAction($actionType)
        );
    }

    protected static function indexAction(AutomationActionInterface $actionType): array
    {
        return [static::classToSlug($actionType::class) =>
            new AutomationActionType($actionType::class, $actionType->title)];
    }

    public function get(string $resourceId): AutomationActionType|null
    {
        return $this->actions->get($resourceId);
    }

    public function all(): array
    {
        return $this->actions->all();
    }
}
